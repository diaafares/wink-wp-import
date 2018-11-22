<?php

namespace Wink\WpImport\Commands;

use stdClass;
use Carbon\Carbon;
use Wink\WinkTag;
use Wink\WinkPost;
use Wink\WinkPage;
use Wink\WinkAuthor;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wink-db-tools:import
                                {--changeAuthor : This option allows you to change the Author of imported posts.}
                                {--withoutPages : This option prevent importing Pages table}
                                {--withoutTags : This option prevent importing Tags table}
                                {--T|truncate : This option will truncate your local Wink tables (wink_pages, wink_posts, wink_tags, wink_posts_tags, wink_authors) prior to the import operation.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import WordPress database';

    private $changeAuthor = false;
    private $authors;
    private $newAuthor;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->option('truncate') ? $this->truncateTables() : '';
        $this->option('withoutPages') ? : $this->importPages();
        $this->option('changeAuthor') ? $this->changeAuthor = true : '';
        $this->importPosts();

        $this->info("Importing Wordpress Database completed successfully.\n");
    }

    protected function truncateTables()
    {
        $confirmed = $this->confirm("You passed 'truncate' option (-T), This will delete all current data in (wink_pages, wink_posts, wink_tags, wink_posts_tags, wink_authors) tables,\nWas this intentional?");

        if (! $confirmed) {
            $this->line('Nothing imported. try again with correct options!');
            die();
        }

        $this->comment('Truncating table started.');

        Schema::disableForeignKeyConstraints();

        DB::table('wink_posts_tags')->truncate();
        WinkPost::truncate();
        WinkTag::truncate();
        WinkAuthor::truncate();

        Schema::enableForeignKeyConstraints();

        $this->info("Tables truncated successfully!\n");
    }

    protected function sanitizePostContent(string $postContent)
    {
        // TODO Prompt for site URL
        $postContent = str_replace('https://YOUR_SITE_URL/wp-content/uploads', '/uploads', $postContent);

        return $postContent;
    }

    protected function importPages()
    {
        $this->comment('Importing Pages started:');

        $oldPages = DB::connection('wordpress')->table('posts')
            ->where('post_status', 'publish')
            ->where('post_type', 'page')
            ->get();

        $bar = $this->output->createProgressBar($oldPages->count());
        $bar->start();

        $oldPages
            ->each(function (stdClass $oldPage) use ($bar) {
                WinkPage::firstOrCreate(
                    ['slug' => $oldPage->post_name],
                    [
                        'id'    => Str::uuid(),
                        'title' => $oldPage->post_title,
                        'body'  => $this->sanitizePostContent($oldPage->post_content),
                    ]
                );
                $bar->advance();
            });

        $bar->finish();
        $this->info("\n" . $oldPages->count() . " Pages imported successfully.\n");
    }

    protected function importPosts()
    {
        $this->checkChangeAuthor();

        $this->comment('Importing Posts started:');

        $oldPosts = DB::connection('wordpress')->table('posts')
            ->where('post_status', 'publish')
            ->where('post_type', 'post')
            ->get();

        $bar = $this->output->createProgressBar($oldPosts->count());
        $bar->start();

        $oldPosts
            ->each(function (stdClass $oldPost) use ($bar) {
                $author = $this->changeAuthor ?
                    $this->newAuthor : $this->authors->firstWhere('wp_author_id', $oldPost->post_author);

                $post = WinkPost::firstOrCreate(
                    ['slug' => $oldPost->post_name],
                    [
                        'id'                     => Str::uuid(),
                        'title'                  => $oldPost->post_title,
                        'body'                   => $this->sanitizePostContent($oldPost->post_content),
                        'excerpt'                => $this->sanitizePostContent($oldPost->post_excerpt),
                        'featured_image_caption' => '',
                        'publish_date'           => Carbon::createFromFormat('Y-m-d H:i:s', $oldPost->post_date),
                        'published'              => true,
                        'author_id'              => $author->id
                    ]
                );

                if (! $this->option('withoutTags')) {
                    $this->attachTags($oldPost, $post);
                }

                $bar->advance();
            });

        $bar->finish();
        $this->info("\n" . $oldPosts->count() . " Posts imported successfully.\n");

    }

    protected function attachTags(stdClass $oldPost, WinkPost $post)
    {
        $tags = DB::connection('wordpress')->select(DB::raw("SELECT * FROM wp_terms
                 INNER JOIN wp_term_taxonomy
                 ON wp_term_taxonomy.term_id = wp_terms.term_id
                 INNER JOIN wp_term_relationships
                 ON wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id
                 WHERE taxonomy = 'post_tag' AND object_id = {$oldPost->ID}"));

        $winkTagsIds = collect($tags)
            ->map(function (stdClass $tag) {
                $winkTag = WinkTag::firstOrCreate(['name' => $tag->name], ['slug' => $tag->slug, 'id' => Str::uuid()]);
                return (string) $winkTag->id;
            });

        $post->tags()->sync($winkTagsIds);
    }

    protected function importAuthors()
    {
        $this->comment('Importing Authors started:');

        $oldAuthors = DB::connection('wordpress')->table('users')->get();

        $bar = $this->output->createProgressBar($oldAuthors->count());
        $bar->start();

        $this->authors = $oldAuthors
            ->map(function (stdClass $oldAuthor) use ($bar) {
                $winkAuthor = WinkAuthor::firstOrCreate(
                    ['email' => $oldAuthor->user_email],
                    [
                        'id'       => Str::uuid(),
                        'slug'     => $oldAuthor->user_login,
                        'name'     => $oldAuthor->display_name,
                        'bio'      => '',
                        'password' => bcrypt('123456'), // TODO prompt for password
                    ]
                );

                $winkAuthor->wp_author_id = $oldAuthor->ID;

                $bar->advance();
                return $winkAuthor;
            });

        $bar->finish();
        $this->info("\n" . $oldAuthors->count() . " Authors imported successfully.\n");
    }

    protected function checkChangeAuthor()
    {
        if ($this->changeAuthor) {
            $winkAuthors = WinkAuthor::all();

            if ($winkAuthors->isEmpty()) {
                $this->line('No Authors in wink_authors table!');
                die();
            }

            $authorChoices = $winkAuthors->map(function ($author) {
                return $author->name . ': ' . $author->email;
            })->toArray();

            list(, $winkAuthorEmail) = explode(': ', strip_tags(
                $this->choice(
                    'What is your name? <comment>(Use Up and Down keys to cycle between choices)</comment>',
                    $authorChoices
                )
            ));

            $this->newAuthor = $winkAuthors->firstWhere('email', $winkAuthorEmail);

        } else {
            $this->importAuthors();
        }
    }
}

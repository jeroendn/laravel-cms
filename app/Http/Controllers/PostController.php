<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Contracts\View\View;

class PostController extends Controller
{
    /**
     * How many posts the home page teases before pointing at the archive.
     */
    private const int RECENT = 5;

    public function home(): View
    {
        return view('home', [
            'posts' => Post::published()
                ->latest('published_at')
                ->take(self::RECENT)
                ->get(),
        ]);
    }

    public function index(): View
    {
        return view('posts.index', [
            'posts' => Post::published()
                ->latest('published_at')
                ->simplePaginate(10),
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->isPublished(), 404);

        return view('posts.show', ['post' => $post]);
    }
}

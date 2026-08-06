<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Contracts\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        return view('home', [
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

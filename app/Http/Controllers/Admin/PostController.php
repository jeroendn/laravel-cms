<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PostController extends Controller
{
    public function index(): View
    {
        return view('admin.posts.index', [
            'posts' => Post::query()
                ->latest()
                ->simplePaginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.posts.create');
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        Post::create([
            ...$request->safe(['title', 'slug', 'body']),
            'published_at' => $request->date('published_at'),
        ]);

        return redirect()
            ->route('admin.posts.index')
            ->with('status', __(':Name created.', ['name' => __('post')]));
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.edit', ['post' => $post]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $post->update([
            ...$request->safe(['title', 'slug', 'body']),
            'published_at' => $request->date('published_at'),
        ]);

        return redirect()
            ->route('admin.posts.index')
            ->with('status', __(':Name updated.', ['name' => __('post')]));
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()
            ->route('admin.posts.index')
            ->with('status', __(':Name deleted.', ['name' => __('post')]));
    }
}

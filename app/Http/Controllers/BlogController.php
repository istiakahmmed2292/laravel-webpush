<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use App\Notifications\TestPushNotification;
use App\Notifications\AdminBlogCreatedNotification;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $blogs = Blog::all();
        return view('Blog.index', compact('blogs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('Blog.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'required|string',
    ]);

    // Create the blog
    $blog = Blog::create([
        'title' => $request->title,
        'content' => $request->content,
        'user_id' => Auth::id(),
    ]);

    // Send Web Push notification
    try {
        Auth::user()->notify(new TestPushNotification($blog));
    } catch (\Throwable $e) {
        // Log the error without breaking the request
        Log::error('Web Push notification failed: ' . $e->getMessage());
    }

    // Notify admins about the new blog
    try {
        $admins = User::where('is_admin', 1)
            ->whereKeyNot(Auth::id())
            ->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new AdminBlogCreatedNotification($blog, Auth::user()));
        }
    } catch (\Throwable $e) {
        Log::error('Admin Web Push notification failed: ' . $e->getMessage());
    }

    return redirect()->route('blogs.index')
        ->with('success', 'Blog post created successfully.');
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $blog = Blog::findOrFail($id);
        return view('Blog.show', compact('blog'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $blog = Blog::findOrFail($id);
        
        if ($blog->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to edit this blog.');
        }

        return view('Blog.edit', compact('blog'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $blog = Blog::findOrFail($id);

        if ($blog->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to update this blog.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $blog->update([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return redirect()->route('blogs.index')->with('success', 'Blog post updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $blog = Blog::findOrFail($id);

        if ($blog->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to delete this blog.');
        }

        $blog->delete();

        return redirect()->route('blogs.index')->with('success', 'Blog post deleted successfully.');
    }
}

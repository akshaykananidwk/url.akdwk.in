<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** CMS: custom pages, blog posts, FAQs. */
class ContentController extends Controller
{
    public function pages()
    {
        return view('admin.content.pages', ['pages' => Page::orderBy('title')->get()]);
    }

    public function editPage(?Page $page = null)
    {
        return view('admin.content.page-form', ['page' => $page]);
    }

    public function savePage(Request $request, ?Page $page = null)
    {
        $data = $request->validate([
            'title' => 'required|string|max:190',
            'slug' => 'nullable|string|max:190|regex:/^[a-z0-9\-]+$/',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:190',
            'meta_description' => 'nullable|string|max:500',
            'active' => 'sometimes|boolean',
            'show_in_footer' => 'sometimes|boolean',
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['active'] = $request->boolean('active', true);
        $data['show_in_footer'] = $request->boolean('show_in_footer', true);

        if ($page) {
            $page->update($data);
        } else {
            abort_if(Page::where('slug', $data['slug'])->exists(), 422, __('Slug already in use.'));
            $page = Page::create($data);
        }

        return redirect()->route('admin.content.pages')->with('status', __('Page saved.'));
    }

    public function destroyPage(Page $page)
    {
        $page->delete();

        return back()->with('status', __('Page deleted.'));
    }

    public function posts()
    {
        return view('admin.content.posts', ['posts' => Post::orderByDesc('created_at')->paginate(20)]);
    }

    public function editPost(?Post $post = null)
    {
        return view('admin.content.post-form', ['post' => $post]);
    }

    public function savePost(Request $request, ?Post $post = null)
    {
        $data = $request->validate([
            'title' => 'required|string|max:190',
            'slug' => 'nullable|string|max:190|regex:/^[a-z0-9\-]+$/',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'published' => 'sometimes|boolean',
            'image' => 'nullable|image|max:4096',
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['published'] = $request->boolean('published');
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('blog', setting('storage_disk', 'public'));
        } else {
            unset($data['image']);
        }

        if ($post) {
            if ($data['published'] && ! $post->published_at) {
                $data['published_at'] = now();
            }
            $post->update($data);
        } else {
            abort_if(Post::where('slug', $data['slug'])->exists(), 422, __('Slug already in use.'));
            $data['user_id'] = $request->user()->id;
            $data['published_at'] = $data['published'] ? now() : null;
            $post = Post::create($data);
        }

        return redirect()->route('admin.content.posts')->with('status', __('Post saved.'));
    }

    public function destroyPost(Post $post)
    {
        $post->delete();

        return back()->with('status', __('Post deleted.'));
    }

    public function faqs()
    {
        return view('admin.content.faqs', ['faqs' => Faq::orderBy('sort_order')->get()]);
    }

    public function saveFaq(Request $request, ?Faq $faq = null)
    {
        $data = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:5000',
            'sort_order' => 'nullable|integer',
            'active' => 'sometimes|boolean',
        ]);
        $data['active'] = $request->boolean('active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $faq ? $faq->update($data) : Faq::create($data);

        return back()->with('status', __('FAQ saved.'));
    }

    public function destroyFaq(Faq $faq)
    {
        $faq->delete();

        return back()->with('status', __('FAQ deleted.'));
    }
}

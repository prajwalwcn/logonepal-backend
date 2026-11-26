<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ContentPage;
use App\Models\Faq;
use App\Models\SiteSetting;
use App\Services\Admin\AdminActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminContentController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'pages' => ContentPage::query()->with('creator:id,name')->latest()->get(),
                'faqs' => Faq::query()->orderBy('sort_order')->get(),
                'announcements' => Announcement::query()->latest()->get(),
                'seo' => SiteSetting::get('seo', $this->seoDefaults()),
            ],
        ]);
    }

    public function storePage(Request $request): JsonResponse
    {
        $data = $this->pageData($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
        $data['created_by'] = $request->user()->id;
        $page = ContentPage::create($data);
        $this->activity->log('content.page.created', $page, "Created content page {$page->title}.");

        return response()->json(['data' => $page], 201);
    }

    public function updatePage(Request $request, ContentPage $page): JsonResponse
    {
        $data = $this->pageData($request, $page);
        if (($data['status'] ?? null) === 'published' && $page->published_at === null) {
            $data['published_at'] = now();
        }
        $page->update($data);
        $this->activity->log('content.page.updated', $page, "Updated content page {$page->title}.", $data);

        return response()->json(['data' => $page->fresh()]);
    }

    public function destroyPage(ContentPage $page): JsonResponse
    {
        $title = $page->title;
        $page->delete();
        $this->activity->log('content.page.deleted', null, "Deleted content page {$title}.");

        return response()->json(['message' => 'Page deleted.']);
    }

    public function storeFaq(Request $request): JsonResponse
    {
        $faq = Faq::create($request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:10000'],
            'category' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]));
        $this->activity->log('content.faq.created', $faq, 'Created FAQ.');

        return response()->json(['data' => $faq], 201);
    }

    public function updateFaq(Request $request, Faq $faq): JsonResponse
    {
        $faq->update($request->validate([
            'question' => ['sometimes', 'string', 'max:500'],
            'answer' => ['sometimes', 'string', 'max:10000'],
            'category' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]));
        $this->activity->log('content.faq.updated', $faq, 'Updated FAQ.');

        return response()->json(['data' => $faq->fresh()]);
    }

    public function destroyFaq(Faq $faq): JsonResponse
    {
        $faq->delete();

        return response()->json(['message' => 'FAQ deleted.']);
    }

    public function storeAnnouncement(Request $request): JsonResponse
    {
        $announcement = Announcement::create($request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
            'type' => ['sometimes', 'in:info,success,warning,maintenance'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]));
        $this->activity->log('content.announcement.created', $announcement, "Created announcement {$announcement->title}.");

        return response()->json(['data' => $announcement], 201);
    }

    public function updateAnnouncement(Request $request, Announcement $announcement): JsonResponse
    {
        $announcement->update($request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'body' => ['sometimes', 'string', 'max:10000'],
            'type' => ['sometimes', 'in:info,success,warning,maintenance'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]));
        $this->activity->log('content.announcement.updated', $announcement, "Updated announcement {$announcement->title}.");

        return response()->json(['data' => $announcement->fresh()]);
    }

    public function destroyAnnouncement(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted.']);
    }

    public function updateSeo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'default_title' => ['nullable', 'string', 'max:160'],
            'default_description' => ['nullable', 'string', 'max:512'],
            'og_image' => ['nullable', 'string', 'max:2048'],
            'robots' => ['nullable', 'string', 'max:120'],
            'canonical_host' => ['nullable', 'string', 'max:255'],
            'sitemap_enabled' => ['sometimes', 'boolean'],
            'google_verification' => ['nullable', 'string', 'max:255'],
        ]);

        SiteSetting::put('seo', array_merge($this->seoDefaults(), $data), 'seo');
        $this->activity->log('content.seo.updated', null, 'Updated SEO defaults.', $data);

        return response()->json(['data' => SiteSetting::get('seo', $this->seoDefaults())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageData(Request $request, ?ContentPage $page = null): array
    {
        return $request->validate([
            'title' => [$page ? 'sometimes' : 'required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'alpha_dash', 'unique:content_pages,slug'.($page ? ','.$page->id : '')],
            'type' => ['sometimes', 'in:page,homepage,landing,blog,email'],
            'excerpt' => ['nullable', 'string', 'max:512'],
            'body' => ['nullable', 'string', 'max:200000'],
            'hero_image' => ['nullable', 'string', 'max:2048'],
            'sections' => ['nullable', 'array'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:512'],
            'og_image' => ['nullable', 'string', 'max:2048'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function seoDefaults(): array
    {
        return [
            'default_title' => 'Logoforge — AI design platform',
            'default_description' => 'Generate editable logos, social templates and brand systems with AI.',
            'og_image' => null,
            'robots' => 'index,follow',
            'canonical_host' => null,
            'sitemap_enabled' => true,
            'google_verification' => null,
        ];
    }
}

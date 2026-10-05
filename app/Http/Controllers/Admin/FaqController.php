<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('category')->orderBy('sort_order')->get();
        return view('admin.faqs.index', compact('faqs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:3000',
            'sort_order' => 'nullable|integer',
        ]);

        $faq = Faq::create([
            'category' => $validated['category'],
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => true,
        ]);

        AuditLog::log('FAQ_CREATED', 'faq', $faq->id, "Admin created FAQ '{$faq->question}'.");

        return back()->with('success', 'FAQ question created.');
    }

    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);

        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:3000',
            'sort_order' => 'nullable|integer',
            'is_published' => 'boolean',
        ]);

        $faq->update([
            'category' => $validated['category'],
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => $validated['sort_order'] ?? $faq->sort_order,
            'is_published' => $request->has('is_published'),
        ]);

        AuditLog::log('FAQ_UPDATED', 'faq', $faq->id, "Admin updated FAQ.");

        return back()->with('success', 'FAQ updated.');
    }

    public function destroy($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();

        AuditLog::log('FAQ_DELETED', 'faq', $id, "Admin deleted FAQ.");

        return back()->with('success', 'FAQ deleted.');
    }
}

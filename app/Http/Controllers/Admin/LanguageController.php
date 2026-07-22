<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Language;
use Illuminate\Http\Request;

/**
 * Translation manager: languages CRUD + an in-browser editor for the JSON
 * language files (lang/{code}.json). New languages start as a copy of the
 * English strings so translators see every key.
 */
class LanguageController extends Controller
{
    public function index()
    {
        return view('admin.languages.index', [
            'languages' => Language::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|regex:/^[a-z]{2}(-[A-Za-z]{2,4})?$/|unique:languages,code',
            'name' => 'required|string|max:60',
            'rtl' => 'sometimes|boolean',
        ]);
        $data['rtl'] = $request->boolean('rtl');
        $data['active'] = true;

        Language::create($data);

        // Seed the language file from English so every key is present.
        $file = lang_path($data['code'] . '.json');
        if (! file_exists($file)) {
            $en = lang_path('en.json');
            copy(file_exists($en) ? $en : $en, $file);
        }
        AuditLog::record('language.created', null, ['code' => $data['code']]);

        return back()->with('status', __('Language added. Translate its strings in the editor.'));
    }

    public function edit(Language $language)
    {
        $file = lang_path($language->code . '.json');
        $strings = file_exists($file) ? json_decode((string) file_get_contents($file), true) : [];
        $english = json_decode((string) file_get_contents(lang_path('en.json')), true) ?: [];

        // Show every English key; missing translations fall back to English.
        $merged = [];
        foreach ($english as $key => $value) {
            $merged[$key] = $strings[$key] ?? '';
        }

        return view('admin.languages.edit', [
            'language' => $language,
            'strings' => $merged,
            'english' => $english,
            'q' => request('q'),
        ]);
    }

    public function update(Request $request, Language $language)
    {
        $strings = (array) $request->input('strings', []);
        $file = lang_path($language->code . '.json');
        $existing = file_exists($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];

        foreach ($strings as $key => $value) {
            if ($value === null || $value === '') {
                unset($existing[$key]);
            } else {
                $existing[$key] = $value;
            }
        }

        file_put_contents($file, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        AuditLog::record('language.updated', null, ['code' => $language->code]);

        return back()->with('status', __('Translations saved.'));
    }

    public function toggle(Language $language)
    {
        abort_if($language->is_default && $language->active, 422, __('The default language cannot be disabled.'));
        $language->update(['active' => ! $language->active]);

        return back()->with('status', __('Language updated.'));
    }

    public function setDefault(Language $language)
    {
        Language::where('is_default', true)->update(['is_default' => false]);
        $language->update(['is_default' => true, 'active' => true]);
        setting_set('default_language', $language->code);

        return back()->with('status', __('Default language changed.'));
    }

    public function destroy(Language $language)
    {
        abort_if($language->is_default, 422, __('The default language cannot be deleted.'));
        abort_if($language->code === 'en', 422, __('English is the base language and cannot be deleted.'));
        $language->delete();

        return back()->with('status', __('Language deleted. Its file was kept on disk.'));
    }
}

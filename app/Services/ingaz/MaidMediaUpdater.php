<?php

namespace App\Services\ingaz;

use App\Models\ingaz\Maid;
use Illuminate\Http\Request;

/**
 * تحديث صور وفيديو العاملة: مصدر واحد لنافذة «الصور والفيديو» في شاشة العاملة
 * (MaidController::updateMedia) ولصفحة الرفع التي يرسل المساعد رابطها عبر MCP.
 */
class MaidMediaUpdater
{
    public const FILE_FIELDS = ['image', 'cv_file', 'cv_preview', 'video'];

    public function rules(): array
    {
        return [
            'image' => ['nullable', 'image', 'max:10240'],
            'cv_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif,bmp,webp', 'max:20480'],
            'cv_preview' => ['nullable', 'image', 'max:10240'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,avi,wmv,webm,mkv,m4v', 'max:102400'],
            'remove_video' => ['nullable', 'boolean'],
            'video_link' => ['nullable', 'url', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.image' => 'يجب أن تكون الصورة الشخصية ملف صورة صالحًا.',
            'cv_file.mimes' => 'ملف السيرة يجب أن يكون PDF أو صورة صالحة.',
            'cv_preview.image' => 'يجب أن تكون صورة معاينة السيرة ملف صورة صالحًا.',
            'video.mimes' => 'صيغة ملف الفيديو غير مدعومة.',
            'video_link.url' => 'رابط الفيديو غير صالح.',
        ];
    }

    /**
     * يرفع الملفات المرسلة ويستبدل القديمة. يرجع false إن لم يتغير شيء.
     * الملفات القديمة تُحذف بعد نجاح الحفظ فقط، والجديدة تُحذف إن فشل.
     */
    public function apply(Maid $maid, Request $request, array $validated): bool
    {
        $updates = [];
        $oldFiles = [];
        $uploadedFiles = [];

        try {
            foreach (self::FILE_FIELDS as $field) {
                if (!$request->hasFile($field)) {
                    continue;
                }

                $oldFiles[] = $maid->{$field};
                $updates[$field] = uploadMaidFile($request->file($field), 'images/Maid/');
                $uploadedFiles[] = $updates[$field];
            }

            if ($request->boolean('remove_video') && !$request->hasFile('video')) {
                $oldFiles[] = $maid->video;
                $updates['video'] = null;
            }

            if ($request->boolean('remove_video_link')) {
                $updates['video_link'] = null;
            } elseif ($request->exists('video_link')) {
                $updates['video_link'] = $validated['video_link'] ?? null;
            }

            if ($updates === [] || collect($updates)->every(fn ($value, $field) => $maid->{$field} == $value)) {
                array_map('deleteFile', $uploadedFiles);

                return false;
            }

            $maid->update($updates);

            foreach (array_filter($oldFiles) as $oldFile) {
                deleteFile($oldFile);
            }
        } catch (\Throwable $exception) {
            foreach ($uploadedFiles as $uploadedFile) {
                deleteFile($uploadedFile);
            }

            throw $exception;
        }

        return true;
    }
}

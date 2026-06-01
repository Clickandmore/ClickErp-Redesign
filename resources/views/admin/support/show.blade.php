@extends('admin.layout.indexs.index')

@section('page-title', $article->title)

@section('content')
<div class="page-body">
    <div class="container-fluid">
        @include('admin.support._alerts')

        @include('admin.support._nav')

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="h3 mb-1 text-gray-800">{{ $article->title }}</h3>
                <span class="badge bg-light text-dark">{{ $article->category ?: 'عام' }}</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('support.edit', $article) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> تعديل
                </a>
                <a href="{{ route('support.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> رجوع
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                @if ($article->summary)
                    <div class="alert alert-info">{{ $article->summary }}</div>
                @endif

                <div style="white-space: pre-line; line-height: 1.9">{{ $article->content }}</div>

                @if ($article->video_url)
                    <div class="mt-4">
                        <a href="{{ $article->video_url }}" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-play-circle"></i> مشاهدة الفيديو
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @php
            $imageAttachments = $article->attachments->filter(fn ($attachment) => str_starts_with((string) $attachment->mime_type, 'image/'));
            $videoAttachments = $article->attachments->filter(fn ($attachment) => str_starts_with((string) $attachment->mime_type, 'video/'));
            $fileAttachments = $article->attachments->reject(fn ($attachment) => str_starts_with((string) $attachment->mime_type, 'image/') || str_starts_with((string) $attachment->mime_type, 'video/'));
        @endphp

        @if ($imageAttachments->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-image me-1"></i> لقطات الشاشة</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach ($imageAttachments as $attachment)
                            <div class="col-md-6">
                                <a href="{{ Storage::url($attachment->file_path) }}" target="_blank" class="d-block">
                                    <img src="{{ Storage::url($attachment->file_path) }}" alt="{{ $attachment->file_name }}" class="img-fluid rounded border">
                                </a>
                                <div class="small text-muted mt-1">{{ $attachment->file_name }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if ($videoAttachments->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-video me-1"></i> فيديوهات الشرح</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach ($videoAttachments as $attachment)
                            <div class="col-md-6">
                                <video class="w-100 rounded border" controls>
                                    <source src="{{ Storage::url($attachment->file_path) }}" type="{{ $attachment->mime_type }}">
                                </video>
                                <div class="small text-muted mt-1">{{ $attachment->file_name }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if ($fileAttachments->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-paperclip me-1"></i> المرفقات</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach ($fileAttachments as $attachment)
                            <div class="col-md-6 col-lg-4">
                                <a class="d-block border rounded p-3 text-decoration-none" href="{{ Storage::url($attachment->file_path) }}" target="_blank">
                                    <i class="fas fa-file me-2"></i>{{ $attachment->file_name }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@extends('admin.layout.indexs.index')
@section('page-title', 'مشاركة السير عبر واتساب')
@section('content')
<div class="container-fluid py-4" dir="rtl">
    <h3 class="mb-3"><i class="fab fa-whatsapp text-success"></i> مشاركة السير عبر واتساب</h3>
    <div class="card card-body">
        @include('admin.ingaz.maid-cv-shares.partials.form')
    </div>
</div>
@include('admin.ingaz.maid-cv-shares.partials.form-script')
<script>window.initMaidCvShareForm(document.querySelector('.cv-share-form'));</script>
@endsection

@extends('layouts.app')

@section('title', 'Edit Member Access')

@section('content')
<div class="view-wrap" id="pageContent">
  <a href="{{ route('users') }}" class="back-link mb-3 d-inline-flex align-items-center gap-1">
    <i class="bi bi-arrow-left"></i>
    <span>Back to User &amp; Access Management</span>
  </a>

  <form method="POST" action="{{ route('users.update', $member) }}">
    @csrf
    @method('PUT')
    @include('pages._user_form', ['member' => $member])
  </form>
</div>
@endsection

@section('init')
Shell.init({ activeNav: 'users', crumb: 'System', title: 'Edit Member Access' });
@endsection

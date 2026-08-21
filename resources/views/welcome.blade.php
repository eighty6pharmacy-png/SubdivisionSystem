@extends('layouts.app')

@section('title', 'Althesa - Subdivision Management System')

@section('content')
{{-- Hero Section --}}
<section class="hero">
    <div class="hero-bg-pattern"></div>
    <div class="hero-grid-overlay"></div>

    <div class="hero-inner">
        <div class="hero-badge">
            🌿 Premium Residential Community
        </div>
        <h1 class="hero-title">
            A Place You'll<br><em style="color: var(--accent); font-style: italic;">Call Home</em>
        </h1>
        <p class="hero-description">
            Althesa is a growing residential community built around comfort, security, and togetherness — with a simple platform to help residents and administration stay connected.
        </p>
        <div class="hero-actions">
            <a href="/appointment" class="btn btn-primary btn-lg">
                📅 Book an Office Visit
            </a>
            <a href="/login" class="btn btn-outline btn-lg">
                Resident Portal →
            </a>
        </div>
    </div>
</section>

{{-- Features Section --}}
<section class="features" id="features">
    <div class="features-inner">
        <div class="section-header">
            <span class="section-label">System Modules</span>
            <h2 class="section-title">Everything You Need to Manage Your Community</h2>
            <p class="section-description">Althesa combines powerful tools into one seamless platform for subdivision administrators, residents, and visitors.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12V7H5a2 2 0 010-4h14v4"/><path d="M3 5v14a2 2 0 002 2h16v-5"/><path d="M18 12a2 2 0 000 4h4v-4h-4z"/></svg>
                </div>
                <h3 class="feature-title">Billing & Collection Analytics</h3>
                <p class="feature-description">Collection trend analysis on payments, overdue tracking, and automated collection reports for smarter financial decisions.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <h3 class="feature-title">Appointment Scheduling</h3>
                <p class="feature-description">Online booking system for lot inquiries with automated report documentation and schedule management.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                </div>
                <h3 class="feature-title">Visitor PIN Verification</h3>
                <p class="feature-description">Secure 6-digit PIN system for visitor identity verification with an integrated subdivision routing guide.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <h3 class="feature-title">Email Notifications</h3>
                <p class="feature-description">Automated alerts for upcoming billing deadlines, overdue reminders, and important community announcements.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <h3 class="feature-title">Announcements & Incidents</h3>
                <p class="feature-description">Post community-wide announcements and manage incident reports with real-time status tracking.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                </div>
                <h3 class="feature-title">GIS Property Mapping</h3>
                <p class="feature-description">Interactive map visualization of the entire subdivision with real-time occupancy status and lot details.</p>
            </div>
        </div>
    </div>
</section>
@endsection

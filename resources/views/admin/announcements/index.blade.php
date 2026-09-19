@extends('layouts.admin')

@section('title', 'Community Announcements')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-announcements.css') }}">

<div class="ann-container">
    <!-- Header -->
    <div class="ann-header">
        <div class="ann-title">
            <h1>Community Announcements</h1>
            <p>Broadcast important news, updates, and emergency alerts to residents.</p>
        </div>
        <div class="ann-header-actions">
            <div class="ann-search-wrapper">
                <input type="text" id="annSearch" placeholder="Search announcements..." 
                       style="width: 100%; padding: 12px 16px 12px 42px; border-radius: 12px; border: 1px solid var(--ann-border); font-size: 14px; outline: none; transition: all 0.2s; box-shadow: var(--ann-shadow);">
                <svg width="20" height="20" fill="none" stroke="var(--ann-text-sub)" stroke-width="2" viewBox="0 0 24 24" 
                     style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%);">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <button class="btn btn-primary" onclick="openModal()">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                Post New
            </button>
        </div>
    </div>

    <!-- Stats & Filters -->
    <div style="display: flex; flex-direction: column; gap: 24px; margin-bottom: 32px;">
        <div class="ann-stats responsive-grid grid-3">
            <div class="ann-stat-card active-stat" data-status="all" style="cursor: pointer; border-color: var(--ann-primary);">
                <span class="ann-stat-label">Total Active</span>
                <div class="ann-stat-value">
                    <span id="stat-total">{{ count($announcements) }}</span>
                </div>
            </div>
            <div class="ann-stat-card" data-status="published" style="cursor: pointer;">
                <span class="ann-stat-label">Published</span>
                <div class="ann-stat-value" style="color: var(--ann-success);">
                    <span id="stat-published">{{ collect($announcements)->filter(function($a) { return strtolower($a['status']) === 'published'; })->count() }}</span>
                </div>
            </div>
            <div class="ann-stat-card" data-status="archived" style="cursor: pointer;">
                <span class="ann-stat-label">Archived</span>
                <div class="ann-stat-value" style="color: var(--ann-danger);">
                    <span id="stat-archived">{{ collect($announcements)->filter(function($a) { return strtolower($a['status']) === 'archived'; })->count() }}</span>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px; scrollbar-width: none;">
            <button class="btn btn-outline active-filter" data-cat="all">All Categories</button>
            <button class="btn btn-outline" data-cat="Emergency">Emergency</button>
            <button class="btn btn-outline" data-cat="Event">Events</button>
            <button class="btn btn-outline" data-cat="Maintenance">Maintenance</button>
            <button class="btn btn-outline" data-cat="General">General</button>
        </div>
    </div>

    <!-- Grid -->
    <div class="ann-grid" id="annGrid">
        @foreach($announcements as $ann)
        <div class="ann-card" data-title="{{ strtolower($ann['title']) }}" data-cat="{{ $ann['cat'] }}" data-status-val="{{ $ann['status'] }}" data-archived="false">
            <div class="ann-card-header">
                <span class="ann-category cat-{{ strtolower($ann['cat']) }}">{{ $ann['cat'] }}</span>
                <span class="ann-status status-{{ $ann['status'] }}" title="{{ ucfirst($ann['status']) }}"></span>
            </div>
            <h3>{{ $ann['title'] }}</h3>
            <p>{{ $ann['content'] }}</p>
            <div class="ann-footer">
                <div class="ann-meta">
                    <span class="ann-author">{{ $ann['author'] }}</span>
                    <span class="ann-date">📅 {{ date('M d, Y', strtotime($ann['date'])) }}</span>
                </div>
                <div class="ann-actions">
                    <button class="ann-btn-icon btn-restore" title="Restore" style="display: none; color: var(--ann-success);" onclick="restorePost(this.closest('.ann-card'))">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                    <button class="ann-btn-icon btn-edit" title="Edit Announcement">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button class="ann-btn-icon btn-archive" title="Archive" style="color: var(--ann-text-sub);" onclick="archivePost(this.closest('.ann-card'))">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Simple Create Modal Mockup -->
<div id="annModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; animation: fadeIn 0.3s ease;">
    <div style="background: white; width: 100%; max-width: 600px; padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); position: relative;">
        <button onclick="closeModal()" style="position: absolute; right: 24px; top: 24px; background: none; border: none; color: var(--ann-text-sub); cursor: pointer;">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <h2 style="font-size: 24px; font-weight: 800; color: var(--ann-text-main); margin-bottom: 8px;">Create Announcement</h2>
        <p style="color: var(--ann-text-sub); margin-bottom: 32px;">Draft or publish a new broadcast for the community.</p>
        
        <form id="annForm" style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ann-text-main); margin-bottom: 8px;">Announcement Title</label>
                <input type="text" id="annTitle" placeholder="e.g. Schedule Maintenance" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--ann-border); font-size: 14px;" required>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ann-text-main); margin-bottom: 8px;">Category</label>
                <select id="annCat" style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--ann-border); font-size: 14px; outline: none;">
                    <option value="General">General Updates</option>
                    <option value="Emergency">Emergency Alert</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Event">Event</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ann-text-main); margin-bottom: 8px;">Content Message</label>
                <textarea id="annContent" rows="4" placeholder="Describe the details of the announcement..." style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--ann-border); font-size: 14px; font-family: inherit; resize: none;" required></textarea>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 12px;">
                <button type="button" id="btnDraft" class="btn btn-outline" style="flex: 1; padding: 14px;" onclick="createPost('draft')">Save Draft</button>
                <button type="button" id="btnPublish" class="btn btn-primary" style="flex: 2; padding: 14px;" onclick="createPost('published')">Publish Now</button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/views/admin-announcements.css') }}">

<script>
    function openModal() {
        document.getElementById('annModal').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('annModal').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('annSearch');
        const cards = document.querySelectorAll('.ann-card');
        const filterBtns = document.querySelectorAll('[data-cat]');
        const statCards = document.querySelectorAll('.ann-stat-card');

        let activeStatus = 'all';
        let activeCat = 'all';

        // Search
        searchInput.addEventListener('input', function() {
            filterCards();
        });

        // Category Filter
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active-filter'));
                this.classList.add('active-filter');
                activeCat = this.getAttribute('data-cat');
                filterCards();
            });
        });

        // Status Filter (Stat Cards)
        statCards.forEach(card => {
            card.addEventListener('click', function() {
                // Highlight active stat card
                statCards.forEach(c => {
                    c.style.borderColor = 'var(--ann-border)';
                    c.classList.remove('active-stat');
                });
                this.style.borderColor = 'var(--ann-primary)';
                this.classList.add('active-stat');
                
                activeStatus = this.getAttribute('data-status');
                filterCards();
            });
        });

        window.archivePost = function(card) {
            if (confirm('Are you sure you want to archive this announcement?')) {
                card.setAttribute('data-archived', 'true');
                card.querySelector('.btn-archive').style.display = 'none';
                card.querySelector('.btn-edit').style.display = 'none';
                card.querySelector('.btn-restore').style.display = 'flex';
                updateStats();
                filterCards();
            }
        }

        window.restorePost = function(card) {
            card.setAttribute('data-archived', 'false');
            card.querySelector('.btn-archive').style.display = 'flex';
            card.querySelector('.btn-edit').style.display = 'flex';
            card.querySelector('.btn-restore').style.display = 'none';
            updateStats();
            filterCards();
        }

        window.filterCards = function() {
            const query = searchInput.value.toLowerCase();
            const cards = document.querySelectorAll('.ann-card');

            cards.forEach(card => {
                const title = card.getAttribute('data-title');
                const cat = card.getAttribute('data-cat');
                const status = card.getAttribute('data-status-val');
                const isArchived = card.getAttribute('data-archived') === 'true';
                
                const matchesSearch = title.includes(query);
                const matchesCat = activeCat === 'all' || cat === activeCat;
                
                // Filtering Logic
                let matchesStatus = false;
                if (activeStatus === 'archived') {
                    matchesStatus = isArchived;
                } else {
                    // Hidden archived items if not specifically looking at "Archived"
                    if (isArchived) {
                        matchesStatus = false;
                    } else {
                        matchesStatus = (activeStatus === 'all' || status === activeStatus);
                    }
                }

                if (matchesSearch && matchesCat && matchesStatus) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        window.updateStats = function() {
            const cards = document.querySelectorAll('.ann-card');
            const counts = {
                totalActive: 0,
                published: 0,
                scheduled: 0,
                draft: 0,
                archived: 0
            };

            cards.forEach(card => {
                const status = card.getAttribute('data-status-val');
                const isArchived = card.getAttribute('data-archived') === 'true';
                
                if (isArchived) {
                    counts.archived++;
                } else {
                    counts.totalActive++;
                    if (counts[status] !== undefined) counts[status]++;
                }
            });

            document.getElementById('stat-total').textContent = counts.totalActive;
            document.getElementById('stat-published').textContent = counts.published;
            document.getElementById('stat-scheduled').textContent = counts.scheduled;
            document.getElementById('stat-archived').textContent = counts.archived;
            
            // Note: Drafts stat removed from top, but still tracked in counts
        }

        let isPublishing = false;
        window.createPost = async function(status) {
            if (isPublishing) return;
            isPublishing = true;

            const btnDraft = document.getElementById('btnDraft');
            const btnPublish = document.getElementById('btnPublish');
            btnDraft.disabled = true; btnDraft.style.opacity = '0.5'; btnDraft.style.pointerEvents = 'none';
            btnPublish.disabled = true; btnPublish.style.opacity = '0.5'; btnPublish.style.pointerEvents = 'none';
            btnPublish.textContent = 'Publishing...';

            const title = document.getElementById('annTitle').value;
            const cat = document.getElementById('annCat').value;
            const content = document.getElementById('annContent').value;

            if (!title || !content) {
                alert('Please fill in all required fields.');
                isPublishing = false;
                btnDraft.disabled = false; btnDraft.style.opacity = '1'; btnDraft.style.pointerEvents = 'auto';
                btnPublish.disabled = false; btnPublish.style.opacity = '1'; btnPublish.style.pointerEvents = 'auto';
                btnPublish.textContent = 'Publish Now';
                return;
            }

            try {
                const response = await fetch('/admin/announcements', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        title: title,
                        category: cat,
                        content: content
                    })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Announcement posted! An email has been sent to the residents.');
                    window.location.reload();
                } else {
                    alert('Failed to post announcement.');
                    isPublishing = false;
                    btnDraft.disabled = false; btnDraft.style.opacity = '1'; btnDraft.style.pointerEvents = 'auto';
                    btnPublish.disabled = false; btnPublish.style.opacity = '1'; btnPublish.style.pointerEvents = 'auto';
                    btnPublish.textContent = 'Publish Now';
                }
            } catch (error) {
                console.error(error);
                alert('An error occurred.');
                isPublishing = false;
                btnDraft.disabled = false; btnDraft.style.opacity = '1'; btnDraft.style.pointerEvents = 'auto';
                btnPublish.disabled = false; btnPublish.style.opacity = '1'; btnPublish.style.pointerEvents = 'auto';
                btnPublish.textContent = 'Publish Now';
            }
        }
    });
</script>
@endsection

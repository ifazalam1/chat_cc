<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chattermate AI Model Comparison</title>
    {{-- @include('admin.layouts.analytics') --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    
    <!-- MathJax for mathematical expressions -->
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    
    <!-- Chart.js for graphs and charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- PDF.js for PDF preview -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    
    <!-- Mammoth.js for DOCX preview -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>
    
    <link rel="shortcut icon" type="image/png" href="{{ config('filesystems.disks.azure.url') . config('filesystems.disks.azure.container') . '/' . $siteSettings->favicon }}">
    
    <!-- MathJax Configuration -->
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\(', '\\)']],
                displayMath: [['$$', '$$'], ['\\[', '\\]']],
                processEscapes: true,
                processEnvironments: true
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
            }
        };
        
        // PDF.js worker configuration
        if (typeof pdfjsLib !== 'undefined') {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    </script>

    <style>
        /* Modern Color Palette */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #8b5cf6;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Smooth slide up animation */
        @keyframes slideUp {
            from {
                opacity: 0;
                max-height: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                max-height: 500px;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 1;
                max-height: 500px;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                max-height: 0;
                transform: translateY(10px);
            }
        }

        #profile-details {
            overflow: hidden;
            transition: all 0.3s ease-out;
        }

        #profile-details:not(.hidden) {
            animation: slideUp 0.3s ease-out;
        }

        /* Online indicator pulse */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .bg-green-500 {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Chevron rotation */
        #profile-chevron {
            transition: transform 0.3s ease;
        }

        #profile-details:not(.hidden) ~ button #profile-chevron {
            transform: rotate(180deg);
        }

        /* User Profile Section in Sidebar */
        .sidebar .user-profile-section {
            background: linear-gradient(135deg, #f3e7ff 0%, #e0f2fe 100%);
        }

        /* Smooth transitions for profile actions */
        .sidebar .user-profile-section a,
        .sidebar .user-profile-section button {
            transition: all 0.2s ease;
        }

        /* Badge animation on hover */
        .sidebar .user-profile-section .plan-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        /* Stats card hover effect */
        .sidebar .user-profile-section .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Export message styling */
        .file-download-card {
            animation: slideInUp 0.4s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .file-download-card:hover {
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.3);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }

        .file-download-card a:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        /* Export assistant message - make it look special */
        .assistant-response:has(.file-download-card) {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-left: 4px solid #22c55e;
        }

                /* Table export buttons */
        .table-export-buttons {
            animation: slideDown 0.3s ease-out;
        }

        .export-table-btn {
            transition: all 0.2s ease;
        }

        .export-table-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .export-table-btn:active {
            transform: translateY(0);
        }

        /* Bounce in animation for modal */
        @keyframes bounce-in {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                transform: scale(1);
            }
        }

        .animate-bounce-in {
            animation: bounce-in 0.5s ease-out;
        }

        /* Share modal animations */
        #share-modal {
            animation: fadeIn 0.2s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .share-action:hover {
            background-color: #dbeafe;
            color: #1e40af;
        }
        /* ✅ Minimal attachment styling */
        .user-prompt .truncate {
            max-width: 200px;
        }
                /* ✅ User message attachment styling */
        .user-prompt .border-t {
            margin-top: 12px;
            padding-top: 12px;
        }

        /* Smooth hover effect for preview button */
        .user-prompt button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* File icon animation */
        .user-prompt i.la-file,
        .user-prompt i.la-file-pdf,
        .user-prompt i.la-file-word {
            transition: transform 0.2s ease;
        }

        .user-prompt button:hover i {
            transform: scale(1.1);
        }

        /* ✅ IMPROVED: Inline attachment badge styling */
        .attachment-badge-inline {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 8px;
            margin-top: 8px;
            backdrop-filter: blur(4px);
        }

        /* Preview button mini */
        .preview-button-mini {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 6px;
            color: #8b5cf6;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .preview-button-mini:hover {
            background: rgba(139, 92, 246, 0.2);
            border-color: rgba(139, 92, 246, 0.5);
            transform: translateY(-1px);
        }

        .preview-button-mini i {
            font-size: 14px;
        }

        /* Better file name display */
        .attachment-badge-inline .font-medium {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Model search styling */
        #model-search-input:focus {
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        .dropdown-search-container {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* Smooth transitions for filtered items */
        .dropdown-model {
            transition: opacity 0.2s ease, max-height 0.2s ease;
        }

        .dropdown-provider {
            transition: opacity 0.2s ease, max-height 0.2s ease;
        }

        /* Highlight search matches (optional) */
        #model-dropdown-fixed .dropdown-model:hover {
            background-color: #f3f4f6;
        }

        /* Three-dot menu styles */
        .conversation-actions-menu {
            position: relative;
        }

        .conversation-menu-button {
            padding: 4px 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .conversation-menu-button:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .conversation-actions-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 4px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 150px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
        }

        .conversation-actions-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .conversation-action-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            cursor: pointer;
            transition: background-color 0.2s;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #374151;
        }

        .conversation-action-item:last-child {
            border-bottom: none;
        }

        .conversation-action-item:hover {
            background-color: #f9fafb;
        }

        .conversation-action-item i {
            font-size: 16px;
        }

        .conversation-action-item.archive-action:hover {
            background-color: #fef3c7;
            color: #92400e;
        }

        .conversation-action-item.edit-action:hover {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .conversation-action-item.delete-action:hover {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Indeterminate checkbox styling */
        #select-all-checkbox:indeterminate {
            background-color: #8b5cf6;
            border-color: #8b5cf6;
        }

        #select-all-checkbox:indeterminate::before {
            content: '';
            display: block;
            width: 10px;
            height: 2px;
            background: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

                /* Selection Mode Styles */
        .conversation-checkbox {
            flex-shrink: 0;
        }

        #bulk-actions-bar {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Archived conversation styling */
        .conversation-item.archived {
            background-color: #f9fafb;
            border-left: 3px solid #f59e0b;
        }

        /* Disabled bulk action buttons */
        button:disabled {
            cursor: not-allowed;
        }
                /* Mode Switch Confirmation Modal */
        .mode-switch-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            backdrop-filter: blur(4px);
        }

        .mode-switch-modal-content {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mode-switch-modal-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .mode-switch-modal-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .mode-switch-modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }

        .mode-switch-modal-body {
            color: #4b5563;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .mode-switch-modal-info {
            background: #f3f4f6;
            border-left: 4px solid #8b5cf6;
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 16px;
            font-size: 14px;
            color: #374151;
        }

        .mode-switch-modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .mode-switch-modal-btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .mode-switch-modal-btn-cancel {
            background: #e5e7eb;
            color: #374151;
        }

        .mode-switch-modal-btn-cancel:hover {
            background: #d1d5db;
        }

        .mode-switch-modal-btn-confirm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .mode-switch-modal-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        /* Search highlight */
        mark {
            background-color: #fef08a;
            padding: 2px 4px;
            border-radius: 2px;
            font-weight: 500;
        }

        /* Loading spinner */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        /* Search result indicator */
        .conversation-item .la-search {
            font-size: 14px;
        }

                /* Search input animation */
        #conversation-search:focus {
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        /* Tooltip animation */
        .group:hover span {
            animation: tooltipFadeIn 0.2s ease-out;
        }

        @keyframes tooltipFadeIn {
            from {
                opacity: 0;
                transform: translate(-50%, -5px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }

        /* Smooth transition for filtered items */
        .conversation-item {
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .conversation-item.hidden {
            display: none;
        }

                /* ✅ NEW: Optimization Mode Toggle Styles */
        .optimization-mode-btn {
            background: transparent;
            color: var(--gray-600);
            border: 1px solid transparent;
            cursor: pointer;
        }

        .optimization-mode-btn:hover {
            background: var(--gray-200);
            color: var(--gray-800);
        }

        .optimization-mode-btn.active {
            background: white;
            color: var(--primary);
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        /* Mode indicator in model panel */
        .model-optimization-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 6px;
            background: rgba(139, 92, 246, 0.2);
            border: 1px solid rgba(139, 92, 246, 0.5);
            border-radius: 4px;
            font-size: 10px;
            color: #a78bfa;
            margin-left: 8px;
        }

        .model-optimization-indicator i {
            font-size: 12px;
        }


        /* Inline translation display */
        .translation-inline {
            margin-top: 12px;
            padding: 12px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-left: 3px solid #0ea5e9;
            border-radius: 8px;
            animation: slideDown 0.3s ease-out;
        }

        .translation-inline-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #bae6fd;
        }

        .translation-inline-label {
            font-size: 12px;
            font-weight: 600;
            color: #0369a1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .translation-inline-actions {
            display: flex;
            gap: 6px;
        }

        .translation-inline-btn {
            padding: 4px 8px;
            font-size: 11px;
            background: white;
            border: 1px solid #bae6fd;
            border-radius: 4px;
            color: #0369a1;
            cursor: pointer;
            transition: all 0.2s;
        }

        .translation-inline-btn:hover {
            background: #f0f9ff;
            border-color: #7dd3fc;
        }

        .translation-inline-text {
            font-size: 14px;
            color: #0c4a6e;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Enhanced Message Design with Left/Right Layout */
        .conversation-entry {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 14px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        /* User message - Right side */
        .user-prompt {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 10px 14px;
            border-radius: 14px 14px 2px 14px;
            font-size: 13px;
            font-weight: 400;
            color: #ffffff;
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.5;
            box-shadow: var(--shadow-sm);

            max-width: 85%;
            margin-left: auto; /* Push to right */
            margin-right: 0;
        }

        /* Assistant message - Left side */
        .assistant-response {
            color: var(--gray-800);
            background: white;
            border: 1px solid var(--gray-200);
            padding: 10px 14px;
            border-radius: 14px 14px 14px 2px;
            box-shadow: var(--shadow-sm);

            max-width: 85%;
            margin-right: auto; /* Push to left */
            margin-left: 0;
        }

        /* Message Action Buttons - Always visible */
        .message-actions {
            display: flex;
            gap: 4px;
            margin-top: 8px;
            opacity: 1;
            transition: all 0.2s ease;
            position: relative;
        }

        /* User message actions - Align right */
        .user-prompt + .message-actions {
            justify-content: flex-end;
            margin-right: 0;
            margin-left: auto;
            max-width: 75%;
        }

        /* Assistant message actions - Align left */
        .assistant-response .message-actions {
            justify-content: flex-start;
            margin-left: 0;
            margin-right: auto;
        }

        /* Optional: Add hover effect for emphasis */
        .conversation-entry:hover .message-actions {
            opacity: 1;
            transform: translateY(-2px);
        }

        .message-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            font-size: 11px;
            color: var(--gray-600);
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
        }

        .message-action-btn:hover {
            background: var(--gray-50);
            color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .message-action-btn i {
            font-size: 14px;
        }

        .message-action-btn.active {
            background: #8b5cf6;
            color: white;
            border-color: #8b5cf6;
        }

        /* For user message actions - purple theme */
        .user-prompt + .message-actions .message-action-btn {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(102, 126, 234, 0.3);
            color: #667eea;
        }

        .user-prompt + .message-actions .message-action-btn:hover {
            background: rgba(255, 255, 255, 1);
            border-color: rgba(102, 126, 234, 0.5);
            color: #5a4dd8;
        }

        /* For assistant message actions */
        .assistant-response .message-actions .message-action-btn {
            background: rgba(139, 92, 246, 0.08);
            border-color: rgba(139, 92, 246, 0.2);
            color: #8b5cf6;
        }

        .assistant-response .message-actions .message-action-btn:hover {
            background: rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.3);
            color: #7c3aed;
        }

        /* Translate dropdown */
        .translate-dropdown {
            position: absolute;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 8px;
            min-width: 200px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 9999;
            display: none;
            margin-top: 4px;
        }

        .translate-dropdown.show {
            display: block;
        }

        .translate-option {
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 13px;
            transition: background 0.2s;
        }

        .translate-option:hover {
            background: #f3f4f6;
        }

        /* Regenerating indicator */
        .regenerating-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 6px;
            font-size: 12px;
            color: #92400e;
            margin-top: 8px;
        }

        .regenerating-indicator .spinner-sm {
            width: 14px;
            height: 14px;
            border: 2px solid #fbbf24;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }


                .close-model-btn {
                    cursor: pointer;
                    color: white !important;
                }

                .close-model-btn:hover {
                    transform: scale(1.1);
                }

                .close-model-btn:hover i {
                    color: #fecaca !important;
                }
                /* Textarea auto-resize */
                #message-input {
                    overflow-y: auto;
                    scrollbar-width: thin;
                    scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
                }

                #message-input::-webkit-scrollbar {
                    width: 6px;
                }

                #message-input::-webkit-scrollbar-track {
                    background: transparent;
                }

                #message-input::-webkit-scrollbar-thumb {
                    background-color: rgba(255, 255, 255, 0.3);
                    border-radius: 3px;
                }

                /* Options dropdown animations */
                #options-dropdown {
                    animation: slideUp 0.2s ease-out;
                    transform-origin: bottom;
                }

                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                /* Dropdown arrow indicator */
                #options-dropdown::before {
                    content: '';
                    position: absolute;
                    bottom: -6px;
                    right: 12px;
                    width: 12px;
                    height: 12px;
                    background: white;
                    transform: rotate(45deg);
                    box-shadow: 2px 2px 3px rgba(0, 0, 0, 0.1);
                }

                /* Make sure dropdown is above other elements */
                .relative {
                    position: relative;
                }

                /* Smooth transitions for all interactive elements */
                #compare-form button,
                #compare-form label {
                    transition: all 0.2s ease;
                }

                /* Active state for options button */
                #options-dropdown-btn.active {
                    color: #fff;
                    transform: rotate(90deg);
                }

                /* Image Modal Styles */
                #image-modal {
                    z-index: 9999;
                }

                #image-modal img {
                    object-fit: contain;
                }

                #modal-close {
                    z-index: 10000;
                }

                /* Image preview in attachment */
                #file-name img {
                    max-height: 80px;
                    max-width: 150px;
                    border-radius: 0.5rem;
                    object-fit: cover;
                }

                #attachment-preview {
                    display: inline-flex !important;
                }

                #attachment-preview.hidden {
                    display: none !important;
                }

                #file-name {
                    max-width: 250px;
                }

                /* Disabled state for create image */
                #create-image-label.opacity-50 {
                    opacity: 0.5;
                }

                #create-image-label.cursor-not-allowed {
                    cursor: not-allowed;
                }

                /* ✅ NEW: Attachment Preview Modal Styles */
                #attachment-preview-modal {
                    z-index: 9999;
                }

                #attachment-preview-modal .modal-content {
                    max-width: 90vw;
                    /* max-height: 90vh; */
                    overflow: hidden;
                }

                #preview-content {
                    max-height: calc(90vh - 120px);
                    overflow-y: auto;
                }

                #preview-content::-webkit-scrollbar {
                    width: 8px;
                }

                #preview-content::-webkit-scrollbar-track {
                    background: #f1f1f1;
                    border-radius: 4px;
                }

                #preview-content::-webkit-scrollbar-thumb {
                    background: #888;
                    border-radius: 4px;
                }

                #preview-content::-webkit-scrollbar-thumb:hover {
                    background: #555;
                }

                .pdf-page-canvas {
                    border: 1px solid #e5e7eb;
                    margin-bottom: 20px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }

                .docx-preview {
                    padding: 20px;
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    font-family: 'Times New Roman', serif;
                    line-height: 1.6;
                }

                .preview-loading {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    padding: 40px;
                    color: #6b7280;
                }

                .preview-loading .spinner {
                    border: 4px solid #f3f4f6;
                    border-top: 4px solid #8b5cf6;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    animation: spin 1s linear infinite;
                }

                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }

                .preview-button {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    padding: 4px 8px;
                    background: #f3f4f6;
                    border: 1px solid #e5e7eb;
                    border-radius: 4px;
                    font-size: 12px;
                    color: #374151;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .preview-button:hover {
                    background: #e5e7eb;
                    color: #1f2937;
                }

                .attachment-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 6px 12px;
                    background: #f9fafb;
                    border: 1px solid #e5e7eb;
                    border-radius: 6px;
                    margin-top: 8px;
                }

                .attachment-badge .file-icon {
                    font-size: 18px;
                }

                .attachment-badge .file-info {
                    flex: 1;
                }

                .attachment-badge .file-name {
                    font-weight: 500;
                    font-size: 13px;
                    color: #111827;
                }

                .attachment-badge .file-size {
                    font-size: 11px;
                    color: #6b7280;
                }

                /* Modern Clean Background */
                .gradient-bg-1 {
                    background: linear-gradient(to right, #1a0a24, #3a0750);
                }

                /* Main content area background */
                #main-content {
                    background: transparent;
                }

                /* Chat interface area background */
                #main-content > .flex-1 {
                    background: transparent;
                }

                .chat-hover:hover {
                    background: rgba(99, 102, 241, 0.1);
                    transition: all 0.2s ease;
                }

                .model-panel {
                    min-height: 500px;
                    height: 100%;
                    max-width: 100%; /* Prevent expansion beyond grid cell */
                    width: 100%; /* Take full width of grid cell */
                    overflow: hidden; /* Contain overflowing content */
                    display: flex;
                    flex-direction: column;
                    transition: all 0.3s ease;
                    background: white;
                    border-radius: 16px;
                    box-shadow: var(--shadow-lg);
                    border: 1px solid var(--gray-200);
                }

            .model-panel.maximized {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1000;
            height: 100vh;
            height: 100dvh; /* Dynamic viewport height for mobile browsers */
            margin: 0;
            border-radius: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
        }

        .model-panel.maximized .model-response {
            flex: 1;
            overflow-y: auto;
            max-height: none;
        }

        /* Hide main chat input when maximized */
        #main-chat-form.hidden-on-maximize {
            display: none !important;
        }

        /* Maximized chat input */
        .maximized-chat-input {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            background: white;
            border-top: 2px solid #e5e7eb;
            padding: 16px;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .maximized-header-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .maximized-header-overlay .model-name {
            font-size: 18px;
            font-weight: 600;
        }

        .maximized-header-overlay .close-maximize-btn {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .maximized-header-overlay .close-maximize-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        #maximized-status-indicator {
            transition: opacity 0.3s ease-in-out;
        }

        #maximized-status-indicator .animate-bounce:nth-child(1) {
            animation: bounce 1s infinite 0ms;
        }

        #maximized-status-indicator .animate-bounce:nth-child(2) {
            animation: bounce 1s infinite 150ms;
        }

        #maximized-status-indicator .animate-bounce:nth-child(3) {
            animation: bounce 1s infinite 300ms;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
                opacity: 1;
            }
            50% {
                transform: translateY(-8px);
                opacity: 0.6;
            }
        }

        /* Adjust model response padding when maximized */
        .model-panel.maximized .model-response {
            padding-top: 70px; /* Space for header */
            padding-bottom: 100px; /* Space for input */
        }

        .model-panel.hidden-panel {
            display: none;
        }

        #models-container.has-maximized {
            position: relative;
        }

        #models-container.has-maximized::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            backdrop-filter: blur(4px);
        }

        .maximize-model-btn {
            cursor: pointer;
            color: white !important;
        }

        .maximize-model-btn:hover {
            transform: scale(1.1);
            color: rgba(255, 255, 255, 0.8) !important;
        }

        .model-response {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden; /* Prevent horizontal expansion of panel */
            padding: 12px;
            max-width: 100%; /* Ensure it doesn't exceed panel width */
        }

       

        .model-conversation {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 100%; /* Prevent expansion beyond panel */
        }

        .conversation-entry {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 12px;
            margin-bottom: 12px;
            max-width: 100%; /* Prevent expansion beyond panel */
            overflow: visible; /* Allow dropdowns to show */
        }

        .conversation-entry:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .user-prompt {
            background: #f3f4f6;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.6;
        }

        /* Message content specific styling */
        .user-prompt a {
            color: #385cd1;
            text-decoration: underline;
            word-break: break-all;
            opacity: 0.9;
        }

        .user-prompt a:hover {
            opacity: 1;
        }

        .assistant-response .message-content {
            color: #1f2937;
            max-width: 100%; /* Prevent expansion beyond panel */
            overflow-x: auto; /* Allow horizontal scroll for wide content */
        }

        .assistant-response {
            color: #111827;
            max-width: 100%; /* Prevent expansion beyond panel */
        }

        .message-content pre {
            background-color: #f6f8fa;
            border-radius: 6px;
            padding: 16px;
            margin: 12px 0;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .message-content code {
            background-color: rgba(175,184,193,0.2);
            border-radius: 6px;
            padding: 0.2em 0.4em;
            font-size: 85%;
        }

        .message-content pre code {
            background-color: transparent;
            padding: 0;
            border-radius: 0;
        }

        /* Table wrapper for horizontal scroll - prevents overlap in multi-panel layouts */
        .message-content .table-wrapper {
            overflow-x: auto;
            overflow-y: visible;
            margin: 12px 0;
            max-width: 100%;
            border: 1px solid #dfe2e5;
            border-radius: 6px;
            background: white;
        }

        /* Scrollbar styling for table wrapper */
        .message-content .table-wrapper::-webkit-scrollbar {
            height: 8px;
        }

        .message-content .table-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .message-content .table-wrapper::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .message-content .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .message-content table {
            border-collapse: collapse;
            width: 100%;
            min-width: 500px; /* Minimum width for readability */
            margin: 0;
        }

        .message-content th, .message-content td {
            border: 1px solid #dfe2e5;
            padding: 6px 13px;
            white-space: nowrap; /* Prevent text wrapping in cells for better table layout */
        }

        .message-content tr:nth-child(2n) {
            background-color: #f6f8fa;
        }

        .thinking-indicator .dot {
            animation: bounce-delay 1.4s infinite ease-in-out both;
            box-shadow: 0 0 8px rgba(139, 92, 246, 0.6);
            background: linear-gradient(135deg, #a78bfa, #8b5cf6);
        }

        .thinking-indicator .dot:nth-child(1) { animation-delay: -0.32s; }
        .thinking-indicator .dot:nth-child(2) { animation-delay: -0.16s; }
        .thinking-indicator .dot:nth-child(3) { animation-delay: 0s; }

        @keyframes bounce-delay {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }

        .chart-container {
            position: relative;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            min-height: 400px;
        }

        .chart-canvas {
            width: 100% !important;
            height: 400px !important;
        }

        .model-panel-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .model-status {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
        }

        .model-status.waiting {
            background: rgba(255, 193, 7, 0.8);
            color: #000;
        }

        .model-status.running {
            background: rgba(40, 167, 69, 0.8);
            animation: pulse 2s infinite;
        }

        .model-status.completed {
            background: rgba(40, 167, 69, 0.8);
        }

        .model-status.error {
            background: rgba(220, 53, 69, 0.8);
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .sidebar {
            transition: transform 0.3s ease;
            position: fixed;
            height: 100vh;
            height: 100dvh; /* Dynamic viewport height for mobile browsers */
            background: white;
            box-shadow: var(--shadow-xl);
            border-right: 1px solid var(--gray-200);
        }
        .sidebar-hidden {
            transform: translateX(-100%);
        }
        .sidebar-visible {
            transform: translateX(0);
        }

        /* Main content transition */
        .main-content {
            transition: margin-left 0.3s ease;
        }
        .main-content-shifted {
            margin-left: 320px;
        }
        .main-content-normal {
            margin-left: 0;
        }

        /* Conversation list scrolling */
        .conversation-list-container {
            max-height: calc(100vh - 200px);
            max-height: calc(100dvh - 200px); /* Dynamic viewport height for mobile browsers */
            overflow-y: auto;
        }

        /* Tooltip fix - prevent layout shift */
        .stat-tooltip {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 0.5rem;
            padding: 0.375rem 0.75rem;
            background-color: #111827;
            color: white;
            font-size: 0.75rem;
            border-radius: 0.5rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease;
            pointer-events: none;
            z-index: 9999;
        }

        .stat-item:hover .stat-tooltip {
            opacity: 1;
            visibility: visible;
        }

        .conversation-item {
            transition: all 0.2s ease;
            border-radius: 8px;
        }

        .conversation-item:hover {
            background-color: var(--gray-50);
            transform: translateX(2px);
        }

        .delete-conversation-btn {
            transition: opacity 0.2s ease, color 0.2s ease;
        }

        .delete-conversation-btn:hover {
            color: var(--danger) !important;
        }

        .copy-code-button {
            position: absolute;
            right: 8px;
            top: 8px;
            opacity: 0;
            transition: opacity 0.2s ease;
            background-color: #f6f8fa;
            border: 1px solid #e1e4e8;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 12px;
            cursor: pointer;
        }

        .code-block-container:hover .copy-code-button {
            opacity: 1;
        }

        .copy-code-button:hover {
            background-color: #e1e4e8;
        }

        .copy-code-button.copied {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;                    /* ✅ NEW: Aligns to right edge */
            top: 100%;                   /* ✅ NEW: Positions below button */
            margin-top: 8px;             /* ✅ NEW: Spacing from button */
            background-color: white;
            min-width: 250px;
            max-width: 350px;            /* ✅ NEW: Prevents excessive width */
            box-shadow: var(--shadow-xl);
            z-index: 9999;               /* ✅ UPDATED: Higher z-index */
            border-radius: 12px;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid var(--gray-200);
        }

        .dropdown-content.show {
            display: block;
        }

        .dropdown-provider {
            padding: 10px 16px;
            background-color: var(--gray-100);
            font-weight: 600;
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-700);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dropdown-model {
            padding: 10px 16px;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid var(--gray-100);
        }

        .dropdown-model:hover {
            background-color: var(--gray-50);
        }

        .dropdown-model.selected {
            background-color: #eef2ff;
            color: var(--primary);
            font-weight: 500;
        }

        /* Responsive grid classes - minmax prevents expansion beyond grid cell */
        .models-grid-1 { grid-template-columns: minmax(0, 1fr); }
        .models-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .models-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .models-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

        @media (max-width: 1536px) {
            .models-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 1024px) {
            .models-grid-4, .models-grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 640px) {
            .models-grid-4, .models-grid-3, .models-grid-2 { grid-template-columns: minmax(0, 1fr); }
        }

        /* ========================================
           MOBILE RESPONSIVE STYLES
           ======================================== */

        @media (max-width: 768px) {
            /* Header adjustments */
            .px-4 {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            /* Header row - reduce gap */
            .px-4.py-2\\.5 .flex.items-center.justify-between {
                gap: 0.5rem !important;
            }

            /* Center section - reduce gap */
            .flex.items-center.gap-2.flex-1.justify-center {
                gap: 0.5rem !important;
            }

            /* Hide desktop stats, show mobile version */
            .stats-desktop {
                display: none !important;
            }

            .stats-mobile {
                display: block !important;
            }

            /* Hide text labels on small screens, keep icons */
            h1 {
                font-size: 1rem !important;
            }

            /* Hide "Modes:" label on mobile */
            .flex.items-center.gap-2 > span.text-sm.font-medium.text-white\/80 {
                display: none !important;
            }

            /* Mode toggle container - reduce gap */
            .flex.items-center.gap-2:has(.optimization-mode-btn) {
                gap: 0.25rem !important;
            }

            /* Mode toggle wrapper - reduce padding */
            .bg-gray-100.rounded-lg.p-0\\.5 {
                padding: 0.25rem !important;
            }

            /* Mode toggle buttons - make more compact */
            .optimization-mode-btn {
                padding: 0.375rem 0.375rem !important;
                font-size: 0.75rem !important;
            }

            .optimization-mode-btn span.font-medium {
                display: none !important; /* Hide text, show only emoji */
            }

            /* Model dropdown button - much more compact */
            #model-dropdown-btn {
                padding: 0.25rem 0.5rem !important;
                font-size: 0.625rem !important;
                gap: 0.25rem !important;
            }

            #model-dropdown-btn i.las.la-robot {
                font-size: 0.75rem !important;
            }

            #model-dropdown-btn i.las.la-chevron-down {
                font-size: 0.625rem !important;
            }

            #model-dropdown-btn span {
                font-size: 0.625rem !important;
            }

            /* Sidebar - full width on mobile */
            #sidebar {
                width: 100% !important;
                max-width: 100% !important;
            }

            /* Chat input area - more compact */
            #compare-form {
                padding: 0.75rem !important;
            }

            #message-input {
                font-size: 0.875rem !important;
                padding: 0.625rem !important;
            }

            /* Models container - adjust gap */
            #models-container {
                gap: 0.5rem !important;
                margin-bottom: 0.5rem !important;
            }

            /* Chat cards - more compact */
            .chat-card {
                min-height: 400px !important;
            }

            .chat-card .p-3 {
                padding: 0.625rem !important;
            }

            /* Model name in card header */
            .chat-card h3 {
                font-size: 0.875rem !important;
            }

            /* Action buttons - smaller */
            .chat-card button {
                padding: 0.375rem !important;
            }

            /* Message content */
            .message-content {
                font-size: 0.875rem !important;
            }

            /* User message bubbles */
            .user-prompt {
                max-width: 95% !important;
                font-size: 0.875rem !important;
            }

            /* Assistant response */
            .assistant-response {
                font-size: 0.875rem !important;
            }

            /* Model dropdown */
            #model-dropdown-fixed {
                left: 0 !important;
                right: 0 !important;
                width: calc(100vw - 1.5rem) !important;
                max-width: none !important;
                margin: 0 0.75rem !important;
            }

            /* Attachment preview */
            #attachment-preview {
                max-width: 100% !important;
            }

            /* Copy buttons and actions - more touch-friendly */
            button, .btn {
                min-height: 44px; /* iOS recommendation for touch targets */
            }

            /* File attachment button */
            .las.la-paperclip {
                font-size: 1.25rem !important;
            }

            /* Modal adjustments */
            .modal-content {
                width: 95vw !important;
                max-width: 95vw !important;
            }

            /* Conversation list items */
            .conversation-item {
                padding: 0.75rem !important;
            }

            /* Bulk actions bar */
            #bulk-actions-bar {
                padding: 0.75rem !important;
            }

            #bulk-actions-bar .flex {
                flex-wrap: wrap !important;
                gap: 0.5rem !important;
            }

            /* Share modal */
            #share-modal .modal-content {
                padding: 1rem !important;
            }

            /* Profile section */
            #toggle-profile-section {
                padding: 0.75rem !important;
            }

            /* Stats display */
            .flex.items-center.gap-2.text-xs {
                gap: 0.25rem !important;
            }
        }

        @media (max-width: 640px) {
            /* Extra small screens - even more compact */
            h1 {
                font-size: 0.875rem !important;
            }

            /* Stack header elements */
            .px-4.py-2\\.5 > .flex {
                flex-wrap: wrap !important;
            }

            /* Center section full width */
            .flex.items-center.gap-2.flex-1.justify-center {
                width: 100% !important;
                justify-content: flex-start !important;
                margin-top: 0.5rem !important;
            }

            /* Model dropdown button - ultra compact */
            #model-dropdown-btn {
                padding: 0.2rem 0.4rem !important;
                font-size: 0.6rem !important;
                gap: 0.2rem !important;
            }

            #model-dropdown-btn i.las.la-robot {
                font-size: 0.7rem !important;
            }

            #model-dropdown-btn i.las.la-chevron-down {
                font-size: 0.55rem !important;
            }

            #model-dropdown-btn span {
                font-size: 0.6rem !important;
            }

            /* Chat cards - minimum comfortable height */
            .chat-card {
                min-height: 350px !important;
            }

            /* Inline controls - adjust spacing */
            .flex.items-center.gap-1.p-1\\.5 {
                gap: 0.25rem !important;
                padding: 0.5rem !important;
            }

            /* Search input in dropdown */
            #model-search-input {
                font-size: 0.875rem !important;
            }

            /* Dropdown models list */
            .dropdown-model {
                padding: 0.625rem !important;
                font-size: 0.875rem !important;
            }

            /* Provider headers in dropdown */
            .dropdown-provider {
                padding: 0.5rem !important;
                font-size: 0.75rem !important;
            }

            /* Conversation search */
            #conversation-search {
                font-size: 0.875rem !important;
            }

            /* Sidebar header buttons */
            #sidebar .p-3 {
                padding: 0.625rem !important;
            }

            /* Sidebar action buttons */
            #new-comparison, #goto-dashboard {
                padding: 0.5rem !important;
            }

            /* User message max width */
            .user-prompt {
                max-width: 98% !important;
            }

            /* Send button */
            button[type="submit"] {
                padding: 0.625rem 1rem !important;
                font-size: 0.875rem !important;
            }

            /* Table overflow on mobile */
            .assistant-response table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            /* Code blocks */
            .assistant-response pre {
                font-size: 0.75rem !important;
                padding: 0.5rem !important;
            }
        }

        @media (max-width: 480px) {
            /* Very small screens - ultra compact */
            .optimization-mode-btn {
                padding: 0.25rem !important;
                min-width: 32px !important;
            }

            .optimization-mode-btn span {
                font-size: 0.875rem !important; /* Smaller emoji */
            }

            /* Model dropdown button - super compact */
            #model-dropdown-btn {
                padding: 0.2rem 0.3rem !important;
                font-size: 0.55rem !important;
                gap: 0.15rem !important;
            }

            #model-dropdown-btn i.las.la-robot {
                font-size: 0.65rem !important;
            }

            #model-dropdown-btn i.las.la-chevron-down {
                font-size: 0.5rem !important;
            }

            #model-dropdown-btn span {
                font-size: 0.55rem !important;
            }

            /* Title even smaller or hide */
            h1 {
                display: none !important;
            }

            /* Menu button - smaller */
            #toggle-sidebar {
                padding: 0.375rem !important;
            }

            #toggle-sidebar i {
                font-size: 1rem !important;
            }

            /* Models container gap */
            #models-container {
                gap: 0.375rem !important;
            }

            /* Chat card heights */
            .chat-card {
                min-height: 300px !important;
            }

            /* Message font sizes */
            .message-content,
            .user-prompt,
            .assistant-response {
                font-size: 0.8125rem !important;
            }

            /* Input textarea */
            #message-input {
                font-size: 0.8125rem !important;
                min-height: 36px !important;
            }

            /* Send button icon only */
            button[type="submit"] span {
                display: none !important;
            }

            button[type="submit"] {
                padding: 0.625rem !important;
                min-width: 44px !important;
            }
        }

        /* ========================================
           MOBILE TOUCH & INTERACTION IMPROVEMENTS
           ======================================== */

        @media (max-width: 768px) {
            /* Smooth scrolling for better mobile experience */
            html {
                -webkit-overflow-scrolling: touch;
            }

            /* Prevent text size adjustment on mobile */
            body {
                -webkit-text-size-adjust: 100%;
                -moz-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }

            /* Better tap highlighting */
            * {
                -webkit-tap-highlight-color: rgba(124, 58, 237, 0.1);
            }

            /* Improve scrolling in chat areas */
            .overflow-y-auto,
            .overflow-auto {
                -webkit-overflow-scrolling: touch;
                scroll-behavior: smooth;
            }

            /* Fix for iOS Safari bottom bar issue */
            #main-content {
                min-height: -webkit-fill-available;
                min-height: 100dvh; /* Dynamic viewport height for mobile browsers */
            }

            /* Safe area insets for devices with notches */
            #main-content,
            .sidebar {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
                padding-left: env(safe-area-inset-left);
                padding-right: env(safe-area-inset-right);
            }

            /* Ensure input area respects safe area */
            #compare-form {
                padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));
            }

            /* Prevent zoom on input focus (iOS) */
            input[type="text"],
            input[type="search"],
            textarea,
            select {
                font-size: 16px !important;
            }

            /* Better touch targets for icons */
            i.las, i.la {
                min-width: 24px;
                min-height: 24px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            /* Smooth transitions for mobile interactions */
            button,
            a,
            .clickable {
                transition: background-color 0.2s ease, transform 0.1s ease;
            }

            button:active,
            a:active,
            .clickable:active {
                transform: scale(0.98);
            }

            /* Improve dropdown visibility on mobile */
            .dropdown-content {
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25) !important;
            }

            /* Better spacing for touch */
            .chat-card button {
                margin: 0.125rem !important;
            }

            /* Optimize loading states */
            .loading-indicator {
                touch-action: none;
            }

            /* Fix sticky positioning on mobile */
            .sticky {
                position: -webkit-sticky;
                position: sticky;
            }

            /* Prevent horizontal scroll */
            body {
                overflow-x: hidden;
            }

            #main-content,
            #models-container {
                max-width: 100vw;
                overflow-x: hidden;
            }

            /* Improve code block scrolling */
            pre code {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            /* Better image handling */
            img {
                max-width: 100%;
                height: auto;
            }

            /* Fix for modal on mobile */
            .fixed.inset-0 {
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            /* Optimize select dropdowns for touch */
            select {
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M10.293 3.293L6 7.586 1.707 3.293A1 1 0 00.293 4.707l5 5a1 1 0 001.414 0l5-5a1 1 0 10-1.414-1.414z'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 0.75rem center;
                padding-right: 2.5rem;
            }
        }

        /* Debug styles */
        .debug-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 8px;
            margin: 8px 0;
            border-radius: 4px;
            font-size: 12px;
            font-family: monospace;
        }
    </style>
</head>
<body class="gradient-bg-1 min-h-screen flex">

    <!-- Sidebar for conversation history -->
    <div id="sidebar" class="sidebar sidebar-hidden fixed inset-y-0 left-0 z-40 w-80 bg-white shadow-lg flex flex-col overflow-hidden">
        <!-- Sidebar Header -->
        <div class="p-3 border-b border-gray-200">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-gray-900">Chat History</h2>

                    <!-- New Comparison Button -->
                    <button
                        id="new-conversation"
                        class="p-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors relative group"
                        title="New Comparison"
                    >
                        <i class="las la-plus text-sm"></i>
                        <span class="absolute top-full left-1/2 transform -translate-x-1/2 mt-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
                            New Comparison
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-[-4px] border-4 border-transparent border-b-gray-800"></div>
                        </span>
                    </button>

                    <!-- Dashboard Button -->
                    <button
                        id="goto-dashboard"
                        class="p-1.5 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors relative group"
                        title="Go to Dashboard"
                    >
                        <i class="las la-home text-sm"></i>
                        <span class="absolute top-full left-1/2 transform -translate-x-1/2 mt-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
                            Dashboard
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-[-4px] border-4 border-transparent border-b-gray-800"></div>
                        </span>
                    </button>
                </div>

                <button id="close-sidebar" class="text-gray-500 hover:text-gray-700 p-1">
                    <i class="las la-times text-xl"></i>
                </button>
            </div>
            
            <!-- Search Input -->
            <div class="relative mb-3">
                <input 
                    type="text" 
                    id="conversation-search" 
                    placeholder="Search conversations..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                >
                <i class="las la-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <button id="clear-search" class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <i class="las la-times-circle"></i>
                </button>
            </div>

            <!-- Filter and Selection Controls -->
            <div class="flex items-center justify-between gap-2">
                <!-- Archive Filter Dropdown -->
                <div class="relative flex-1">
                    <select id="archive-filter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
                        <option value="active">Active Chats</option>
                        <option value="archived">Archived</option>
                        <option value="all">All Chats</option>
                    </select>
                </div>
                
                <!-- Select Mode Toggle -->
                <button id="toggle-select-mode" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap" title="Select multiple">
                    <i class="las la-check-square"></i> Select
                </button>
            </div>
        </div>

        <!-- Bulk Actions Bar (Hidden by default) -->
        <div id="bulk-actions-bar" class="hidden bg-purple-50 border-b border-purple-200 p-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Select All Checkbox -->
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="select-all-checkbox" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded cursor-pointer">
                        <span class="text-sm font-medium text-purple-900">Select All</span>
                    </label>
                    <div class="text-sm font-medium text-purple-900 border-l border-purple-300 pl-3">
                        <span id="bulk-selected-count">0</span> selected
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button id="bulk-archive-btn" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <i class="las la-archive"></i> Archive
                    </button>
                    <button id="bulk-delete-btn" class="px-3 py-1.5 text-xs bg-red-600 text-white rounded hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <i class="las la-trash"></i> Delete
                    </button>
                    <button id="cancel-select-btn" class="px-3 py-1.5 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Conversations List -->
        <div class="flex-1 overflow-y-auto p-4">
            <!-- No results message (hidden by default) -->
            <div id="no-search-results" class="hidden text-center text-gray-500 py-8">
                <i class="las la-search text-4xl mb-2"></i>
                <p>No conversations found</p>
            </div>
            
            <div id="conversations-list" class="space-y-2">
                <!-- Conversations will be loaded here -->
            </div>
        </div>

        <!-- ✅ IMPROVED: Compact User Profile Section (Initially Minimized) -->
        <div class="border-t border-gray-200 bg-white">
            <!-- Profile Header (Always Visible) -->
            <button id="toggle-profile-section" 
                    class="w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors relative">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <div class="relative">
                        <img src="@if (Auth::user()->avatar != ''){{ URL::asset('images/' . Auth::user()->avatar) }}@else{{ URL::asset('build/images/users/avatar-1.jpg') }}@endif" 
                            alt="{{ Auth::user()->name }}" 
                            class="w-9 h-9 rounded-full border-2 border-purple-400 shadow-sm">
                        <!-- Online indicator -->
                        <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white rounded-full"></span>
                    </div>
                    <div class="flex-1 min-w-0 text-left">
                        <h3 class="font-semibold text-gray-900 text-sm truncate">{{ Auth::user()->name }}</h3>
                        <div class="flex items-center gap-1 text-xs">
                            <i class="las la-crown text-yellow-500"></i>
                            <span class="text-gray-600 truncate">{{ Auth::user()->plan->name ?? 'Free' }}</span>
                        </div>
                    </div>
                </div>
                <i class="las la-chevron-up text-gray-400" id="profile-chevron"></i>
            </button>

            <!-- Profile Details (Initially Hidden) -->
            <div id="profile-details" class="hidden bg-gradient-to-br from-purple-50 to-indigo-50 border-t border-gray-100">
                <!-- User Info Header -->
                <div class="bg-gradient-to-br from-purple-600 to-indigo-600 p-4 text-white">
                   
                    
                    <!-- Quick Stats - Compact -->
                    <div class="flex items-center gap-2 text-xs flex-wrap">
                        <div class="flex items-center gap-1 bg-white/20 px-2 py-1 rounded">
                            <i class="las la-coins"></i>
                            <span class="font-semibold">{{ number_format(Auth::user()->tokens_left) }}</span>
                            <span class="text-purple-200">tokens</span>
                        </div>
                        <div class="flex items-center gap-1 bg-white/20 px-2 py-1 rounded">
                            <i class="las la-gem"></i>
                            <span class="font-semibold">{{ number_format(Auth::user()->credits_left) }}</span>
                            <span class="text-purple-200">credits</span>
                        </div>
                        <a href="{{ route('pricing') }}" 
                        class="ml-auto text-xs bg-yellow-400 text-purple-900 px-2 py-1 rounded font-semibold hover:bg-yellow-300 transition-colors" title="Upgrade Plan">
                            <i class="las la-arrow-up"></i> 
                        </a>
                    </div>
                </div>

                <!-- Menu Items -->
                <div class="p-2">
                    <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-purple-100 rounded-lg transition-colors">
                        <i class="las la-th-large text-purple-600 text-lg"></i>
                        <span class="font-medium">Dashboard</span>
                    </a>

                    <a href="{{ route('edit.profile') }}"
                    class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-purple-100 rounded-lg transition-colors">
                        <i class="las la-user-circle text-purple-600 text-lg"></i>
                        <span class="font-medium">My Profile</span>
                    </a>

                    <a href="{{ route('ai.image.gallery') }}" target="_blank"
                    class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-purple-100 rounded-lg transition-colors">
                        <i class="las la-images text-purple-600 text-lg"></i>
                        <span class="font-medium">Image Gallery</span>
                    </a>

                    <a href="{{ url('/billing/portal') }}" target="_blank"
                    class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-purple-100 rounded-lg transition-colors">
                        <i class="las la-credit-card text-purple-600 text-lg"></i>
                        <span class="font-medium">Billing</span>
                    </a>

                    <div class="border-t border-gray-200 my-2"></div>

                    <button onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                            class="w-full flex items-center gap-3 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <i class="las la-sign-out-alt text-lg"></i>
                        <span class="font-medium">Logout</span>
                    </button>
                </div>
            </div>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </div>

    <!-- ✅ NEW: Attachment Preview Modal -->
    <div id="attachment-preview-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80 hidden">
        <div class="modal-content bg-white rounded-lg shadow-2xl overflow-hidden" style="width: 90vw; max-width: 1200px;">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gradient-to-r from-purple-600 to-indigo-600 text-white">
                <div class="flex items-center space-x-3">
                    <i class="las la-file-alt text-2xl"></i>
                    <div>
                        <h3 id="preview-modal-title" class="text-lg font-semibold">File Preview</h3>
                        <p id="preview-modal-filename" class="text-sm opacity-90"></p>
                    </div>
                </div>
                <button id="close-preview-modal" class="text-white hover:bg-white/20 p-2 rounded-lg transition-colors">
                    <i class="las la-times text-2xl"></i>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div id="preview-content" class="p-6 bg-gray-50">
                <div class="preview-loading">
                    <div class="spinner"></div>
                    <p class="mt-4">Loading preview...</p>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex items-center justify-end space-x-3 p-4 border-t border-gray-200 bg-gray-50">
                <button id="download-attachment-btn" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="las la-download mr-2"></i>Download
                </button>
                <button id="close-preview-modal-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- ✅ Share Conversation Modal -->
    <div id="share-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-4 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="las la-share-alt text-2xl"></i>
                        <h3 class="text-lg font-semibold">Share Conversation</h3>
                    </div>
                    <button id="close-share-modal" class="text-white hover:bg-white/20 p-2 rounded-lg transition-colors">
                        <i class="las la-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Body -->
            <div class="p-6">
                <!-- Loading State -->
                <div id="share-loading" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    <p class="mt-2 text-gray-600">Generating share link...</p>
                </div>
                
                <!-- Share Link Display -->
                <div id="share-content" class="hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Share Link</label>
                        <div class="flex items-center gap-2">
                            <input type="text" id="share-url-input" readonly 
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm">
                            <button id="copy-share-link" 
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2"
                                title="Copy link">
                                <i class="las la-copy"></i>
                                <span class="hidden sm:inline">Copy</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Share Info -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start gap-2">
                            <i class="las la-info-circle text-blue-600 text-xl"></i>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold mb-1">Anyone with this link can view this conversation</p>
                                <p>They don't need an account to access it.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="flex items-center justify-between text-sm text-gray-600 mb-4">
                        <div class="flex items-center gap-2">
                            <i class="las la-eye"></i>
                            <span><span id="share-view-count">0</span> views</span>
                        </div>
                        <div class="flex items-center gap-2" id="share-expires-info">
                            <i class="las la-clock"></i>
                            <span id="share-expires-text">Never expires</span>
                        </div>
                    </div>
                    
                    <!-- Expiration Options -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Set Expiration (Optional)</label>
                        <select id="share-expiration" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">Never expires</option>
                            <option value="1">1 day</option>
                            <option value="7">7 days</option>
                            <option value="30">30 days</option>
                            <option value="90">90 days</option>
                        </select>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <button id="regenerate-share-link" 
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            <i class="las la-sync-alt mr-2"></i>Regenerate Link
                        </button>
                        <button id="revoke-share-link" 
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <i class="las la-ban mr-2"></i>Revoke Access
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div id="main-content" class="main-content main-content-normal flex-1 flex flex-col min-h-screen">
        <!-- Compact Single-Row Header -->
        <div class="px-4 py-2.5" style="background: rgb(255 255 255 / 0.1); border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div class="flex items-center justify-between gap-3">
                <!-- Left: Menu + Title -->
                <div class="flex items-center gap-3">
                    <button id="toggle-sidebar" class="text-white hover:bg-white/10 p-2 rounded-lg transition-colors">
                        <i class="las la-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-bold text-white">AI Model Comparison</h1>
                </div>

                <!-- Center: Mode + Model Selection -->
                <div class="flex items-center gap-2 flex-1 justify-center">
                    <!-- Mode Toggle - Compact -->
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-white/80">Modes:</span>
                        <div class="flex items-center bg-gray-100 rounded-lg p-0.5 border border-gray-200">
                            <button type="button" class="optimization-mode-btn active px-3 py-1.5 rounded-md text-sm transition-all flex items-center gap-1.5" data-mode="fixed" title="Manual Select">
                                <span>🎯</span>
                                <span class="font-medium">Manual</span>
                            </button>
                            <button type="button" class="optimization-mode-btn px-3 py-1.5 rounded-md text-sm transition-all flex items-center gap-1.5" data-mode="smart_same" title="Auto-Match">
                                <span>🔄</span>
                                <span class="font-medium">Auto-Match</span>
                            </button>
                            <button type="button" class="optimization-mode-btn px-3 py-1.5 rounded-md text-sm transition-all flex items-center gap-1.5" data-mode="smart_all" title="Auto-Best">
                                <span>✨</span>
                                <span class="font-medium">Auto-Best</span>
                            </button>
                        </div>
                    </div>

                    <!-- Model Dropdown - Compact -->
                    <div class="relative">
                        <button id="model-dropdown-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg transition-colors flex items-center gap-2 text-sm font-medium">
                            <i class="las la-robot"></i>
                            <span id="selected-count">0 Models</span>
                            <i class="las la-chevron-down text-xs"></i>
                        </button>
                        
                        <!-- ✅ Fixed Mode Dropdown - Show all models with search -->
                        <div id="model-dropdown-fixed" class="dropdown-content">
                            <!-- Search Input -->
                            <div class="dropdown-search-container" style="position: sticky; top: 0; z-index: 10; background: white; padding: 12px; border-bottom: 1px solid #e5e7eb;">
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        id="model-search-input" 
                                        placeholder="Search models..." 
                                        class="w-full pl-9 pr-9 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                        autocomplete="off"
                                    >
                                    <i class="las la-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    <button 
                                        type="button" 
                                        id="clear-model-search" 
                                        class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                    >
                                        <i class="las la-times-circle"></i>
                                    </button>
                                </div>
                                <div id="model-search-count" class="text-xs text-gray-500 mt-1 hidden">
                                    <span id="model-match-count">0</span> models found
                                </div>
                            </div>
                            
                            <!-- No Results Message -->
                            <div id="no-model-results" class="hidden text-center py-8 text-gray-500">
                                <i class="las la-search text-3xl mb-2"></i>
                                <p class="text-sm">No models found</p>
                            </div>
                            
                            <!-- Models List -->
                            <!-- Models List -->
                            <div id="models-list-container">
                                @foreach($availableModels as $provider => $models)
                                    <div class="dropdown-provider flex items-center gap-2" data-provider="{{ $provider }}">
                                        <span class="provider-icon-wrapper"></span>
                                        <span>{{ ucfirst($provider) }}</span>
                                    </div>
                                    @foreach($models as $model)
                                        <div class="dropdown-model" 
                                            data-model="{{ $model->openaimodel }}" 
                                            data-provider="{{ $provider }}"
                                            data-display-name="{{ $model->displayname }}"
                                            data-search-text="{{ strtolower($model->displayname . ' ' . $model->openaimodel . ' ' . $provider) }}">
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="models[]" value="{{ $model->openaimodel }}" 
                                                    class="model-checkbox text-purple-600 focus:ring-purple-500"
                                                    data-provider="{{ $provider }}"
                                                    data-display-name="{{ $model->displayname }}"
                                                    data-cost="{{ number_format((float)($model->cost_per_m_tokens), 6, '.', '') }}"
                                                    data-supports-web-search="{{ $model->supports_web_search ? '1' : '0' }}">
                                                <span>{{ $model->displayname }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>          
                        <!-- ✅ Smart Mode Dropdown - Show only providers -->
                        <div id="model-dropdown-smart" class="dropdown-content hidden">
                            @foreach($availableModels as $provider => $models)
                                @php
                                    // Get the first model from this provider for the data-first-model attribute
                                    $firstModel = $models->first();
                                @endphp
                                <div class="dropdown-model"
                                    data-provider="{{ $provider }}"
                                    data-display-name="{{ ucfirst($provider) }} Smart Mode">
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" name="providers[]" value="{{ $provider }}"
                                            class="provider-checkbox text-purple-600 focus:ring-purple-500"
                                            data-provider="{{ $provider }}"
                                            data-display-name="{{ ucfirst($provider) }} Smart Mode"
                                            data-first-model="{{ $firstModel->openaimodel }}">
                                        <span class="font-semibold flex items-center gap-2">
                                            <span class="provider-icon-wrapper-smart"></span>
                                            <span>{{ ucfirst($provider) }}</span>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Right: Stats - Compact -->
                <div class="flex items-center gap-2">
                    <!-- Desktop view - separate stats -->
                    <div class="relative stat-item stats-desktop">
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white/90 text-gray-700 rounded-lg cursor-help border border-white/20">
                            <i class="las la-coins text-sm"></i>
                            <span id="credits_left" class="font-semibold text-sm">{{ auth()->user()->credits_left ?? '∞' }}</span>
                        </div>
                        <div class="stat-tooltip">Credits</div>
                    </div>
                    <div class="relative stat-item stats-desktop">
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white/90 text-gray-700 rounded-lg cursor-help border border-white/20">
                            <i class="las la-database text-sm"></i>
                            <span id="tokens_left" class="font-semibold text-sm">{{ auth()->user()->tokens_left ?? '∞' }}</span>
                        </div>
                        <div class="stat-tooltip">Tokens</div>
                    </div>

                    <!-- Mobile view - combined icon with popover -->
                    <div class="relative stat-item stats-mobile hidden">
                        <button id="stats-mobile-btn" class="flex items-center gap-1 px-2 py-1.5 bg-white/90 text-gray-700 rounded-lg border border-white/20 hover:bg-white transition-colors">
                            <i class="las la-wallet text-base"></i>
                        </button>
                        <!-- Popover tooltip -->
                        <div id="stats-mobile-popover" class="hidden absolute right-0 top-full mt-2 bg-white rounded-lg shadow-xl border border-gray-200 p-3 z-50 min-w-[160px]">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-1.5 text-gray-600">
                                        <i class="las la-coins text-sm"></i>
                                        <span class="text-xs">Credits</span>
                                    </div>
                                    <span id="credits_left_mobile" class="font-semibold text-sm text-gray-900">{{ auth()->user()->credits_left ?? '∞' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-1.5 text-gray-600">
                                        <i class="las la-database text-sm"></i>
                                        <span class="text-xs">Tokens</span>
                                    </div>
                                    <span id="tokens_left_mobile" class="font-semibold text-sm text-gray-900">{{ auth()->user()->tokens_left ?? '∞' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Interface -->
        <div class="flex-1 p-3 overflow-hidden">
            <div class="max-w-full mx-auto h-full flex flex-col">

                <!-- Models Container -->
                <div id="models-container" class="flex-1 grid gap-3 mb-3 overflow-auto">
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center text-gray-400">
                            <i class="las la-robot text-6xl mb-4"></i>
                            <p class="text-lg font-medium">Select models from the dropdown above to start comparing</p>
                        </div>
                    </div>
                </div>

                <!-- Chat Input -->
                <form id="compare-form" class="rounded-lg shadow p-3 border" style="background: rgb(255 255 255 / 0.1); border-color: rgba(255,255,255,0.1);">
                    <!-- File Upload Preview -->
                    <!-- ✅ File Upload Preview with Icon-Only Button -->
                    <div id="attachment-preview" class="hidden bg-indigo-50 p-2 rounded mb-2 inline-block max-w-max border border-indigo-200">
                        <div class="flex items-center space-x-3">
                            <i class="las la-paperclip text-indigo-600 text-xl"></i>
                            <div id="file-name" class="text-indigo-900 text-sm font-medium"></div>
                            <!-- ✅ NEW: Upload Progress Indicator -->
                            <div id="upload-progress" class="hidden flex items-center space-x-2 text-xs text-indigo-600">
                                <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-indigo-600"></div>
                                <span>Uploading...</span>
                            </div>
                            <div class="flex items-center space-x-2 ml-auto">
                                <button type="button" id="preview-attachment-btn" class="text-indigo-600 hover:text-indigo-700 bg-white hover:bg-indigo-100 p-2 rounded transition-colors" title="Preview file">
                                    <i class="las la-eye text-lg"></i>
                                </button>
                                <button type="button" id="remove-file" class="text-red-600 hover:text-red-700 bg-white hover:bg-red-50 p-2 rounded transition-colors" title="Remove file">
                                    <i class="las la-times text-lg"></i>
                                </button>
                            </div>
                        </div>
                        <!-- ✅ NEW: File Status Messages -->
                        <div id="file-status-messages" class="mt-2 space-y-1"></div>
                    </div>

                    <!-- Main Input Area -->
                    <div class="flex items-end gap-2">
                        <!-- Left Side: Input with inline controls -->
                        <div class="flex-1 relative">
                            <div class="flex items-end bg-white/10 border border-white/20 rounded-lg focus-within:ring-2 focus-within:ring-purple-400 focus-within:border-purple-400 transition-all">
                                <!-- Textarea -->
                                <textarea
                                    id="message-input"
                                    name="message"
                                    placeholder="Type your message here..."
                                    class="flex-1 p-2.5 bg-transparent text-white placeholder-gray-300 focus:outline-none resize-none min-h-[40px] max-h-[160px] text-sm"
                                    rows="1"
                                    required></textarea>

                                <!-- Inline Controls -->
                                <div class="flex items-center gap-1 p-1.5 pb-2">
                                    <!-- Attachment Button -->
                                    <label for="file-input" class="cursor-pointer text-gray-300 hover:text-white transition-colors" title="Attach file">
                                        <i class="las la-paperclip text-xl"></i>
                                    </label>
                                    <input type="file" id="file-input" name="files[]" class="hidden"
                                    accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.webp,.gif" multiple>

                                    <!-- Options Dropdown Button -->
                                    <div class="relative">
                                        <button type="button" id="options-dropdown-btn" class="text-gray-300 hover:text-white transition-colors" title="More options">
                                            <i class="las la-sliders-h text-xl"></i>
                                        </button>
                                        
                                        <!-- Dropdown Menu -->
                                        <div id="options-dropdown" class="hidden absolute bottom-full right-0 mb-2 bg-white rounded-lg shadow-lg py-2 min-w-[200px] z-50">
                                            <label for="web-search" class="flex items-center space-x-3 px-4 py-2 hover:bg-gray-100 cursor-pointer transition-colors">
                                                <input type="checkbox" id="web-search" name="web_search" class="text-purple-600 focus:ring-purple-500 rounded">
                                                <div class="flex items-center space-x-2">
                                                    <i class="las la-search text-gray-700"></i>
                                                    <span class="text-gray-700 text-sm">Web Search</span>
                                                </div>
                                            </label>
                                            
                                            <label class="flex items-center space-x-3 px-4 py-2 hover:bg-gray-100 cursor-pointer transition-colors" id="create-image-label">
                                                <input type="checkbox" id="create-image" name="create_image" class="text-purple-600 focus:ring-purple-500 rounded">
                                                <div class="flex items-center space-x-2">
                                                    <i class="las la-image text-gray-700"></i>
                                                    <span class="text-gray-700 text-sm">Generate Image</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side: Action Buttons -->
                        <div class="flex gap-1">
                            <button type="submit" id="send-button"
                                    class="text-white p-2.5 rounded-lg transition-all shadow flex items-center justify-center min-w-[40px] min-h-[40px]" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                                <i class="las la-paper-plane text-lg"></i>
                            </button>

                            <button type="button" id="stop-button"
                                    class="hidden bg-red-600 hover:bg-red-700 text-white p-2.5 rounded-lg transition-all shadow flex items-center justify-center min-w-[40px] min-h-[40px]">
                                <i class="las la-stop text-lg"></i>
                            </button>
                        </div>
                    </div>
                     <!-- Helper Text -->
                    {{-- <div class="text-white/50 text-xs mt-1 px-1">
                        Press Enter to send, Shift+Enter for new line
                    </div> --}}
                </form>
            </div>
        </div>
    </div>

    <script>
        // ✅ Provider icon mapping function
        function getProviderIcon(provider) {
            const icons = {
                'openai': '<svg class="inline-block w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M22.282 9.821a5.985 5.985 0 0 0-.516-4.91 6.046 6.046 0 0 0-6.51-2.9A6.065 6.065 0 0 0 4.981 4.18a5.985 5.985 0 0 0-3.998 2.9 6.046 6.046 0 0 0 .743 7.097 5.98 5.98 0 0 0 .51 4.911 6.051 6.051 0 0 0 6.515 2.9A5.985 5.985 0 0 0 13.26 24a6.056 6.056 0 0 0 5.772-4.206 5.99 5.99 0 0 0 3.997-2.9 6.056 6.056 0 0 0-.747-7.073zM13.26 22.43a4.476 4.476 0 0 1-2.876-1.04l.141-.081 4.779-2.758a.795.795 0 0 0 .392-.681v-6.737l2.02 1.168a.071.071 0 0 1 .038.052v5.583a4.504 4.504 0 0 1-4.494 4.494zM3.6 18.304a4.47 4.47 0 0 1-.535-3.014l.142.085 4.783 2.759a.771.771 0 0 0 .78 0l5.843-3.369v2.332a.08.08 0 0 1-.033.062L9.74 19.95a4.5 4.5 0 0 1-6.14-1.646zM2.34 7.896a4.485 4.485 0 0 1 2.366-1.973V11.6a.766.766 0 0 0 .388.676l5.815 3.355-2.02 1.168a.076.076 0 0 1-.071 0l-4.83-2.786A4.504 4.504 0 0 1 2.34 7.872zm16.597 3.855l-5.833-3.387L15.119 7.2a.076.076 0 0 1 .071 0l4.83 2.791a4.494 4.494 0 0 1-.676 8.105v-5.678a.79.79 0 0 0-.407-.667zm2.01-3.023l-.141-.085-4.774-2.782a.776.776 0 0 0-.785 0L9.409 9.23V6.897a.066.066 0 0 1 .028-.061l4.83-2.787a4.5 4.5 0 0 1 6.68 4.66zm-12.64 4.135l-2.02-1.164a.08.08 0 0 1-.038-.057V6.075a4.5 4.5 0 0 1 7.375-3.453l-.142.08L8.704 5.46a.795.795 0 0 0-.393.681zm1.097-2.365l2.602-1.5 2.607 1.5v2.999l-2.597 1.5-2.607-1.5z"/></svg>',
                'anthropic': '<svg class="inline-block w-5 h-5" viewBox="0 0 120 120"><rect width="120" height="120" rx="24" fill="#CC9B7A"/><g fill="#FFF" transform="translate(60,60)"><rect x="-4" y="-35" width="8" height="30" rx="4"/><rect x="-4" y="5" width="8" height="30" rx="4"/><rect x="-35" y="-4" width="30" height="8" ry="4"/><rect x="5" y="-4" width="30" height="8" ry="4"/><rect x="-27" y="-27" width="8" height="25" rx="4" transform="rotate(45)"/><rect x="19" y="-27" width="8" height="25" rx="4" transform="rotate(-45)"/><rect x="-27" y="19" width="8" height="25" rx="4" transform="rotate(-45)"/><rect x="19" y="19" width="8" height="25" rx="4" transform="rotate(45)"/></g></svg>',
                'claude': '<svg class="inline-block w-5 h-5" viewBox="0 0 120 120"><rect width="120" height="120" rx="24" fill="#CC9B7A"/><g fill="#FFF" transform="translate(60,60)"><rect x="-4" y="-35" width="8" height="30" rx="4"/><rect x="-4" y="5" width="8" height="30" rx="4"/><rect x="-35" y="-4" width="30" height="8" ry="4"/><rect x="5" y="-4" width="30" height="8" ry="4"/><rect x="-27" y="-27" width="8" height="25" rx="4" transform="rotate(45)"/><rect x="19" y="-27" width="8" height="25" rx="4" transform="rotate(-45)"/><rect x="-27" y="19" width="8" height="25" rx="4" transform="rotate(-45)"/><rect x="19" y="19" width="8" height="25" rx="4" transform="rotate(45)"/></g></svg>',
                'google': '<svg class="inline-block w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>',
                'gemini': '<svg class="inline-block w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>',
                'grok': '<svg class="inline-block w-5 h-5" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="4" fill="#000000"/><path d="M17.5 6L12 12L17.5 18M6.5 6L12 12L6.5 18" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'perplexity': '<svg class="inline-block w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10" fill="#20808D"/><text x="12" y="16" font-size="12" text-anchor="middle" fill="white" font-weight="bold">P</text></svg>',
                'mistral': '<svg class="inline-block w-5 h-5" viewBox="0 0 24 24" fill="#F2A73B"><rect width="4" height="4" x="0" y="0"/><rect width="4" height="4" x="5" y="0"/><rect width="4" height="4" x="10" y="0"/><rect width="4" height="4" x="15" y="0"/><rect width="4" height="4" x="20" y="0"/><rect width="4" height="4" x="0" y="5"/><rect width="4" height="4" x="20" y="5"/><rect width="4" height="4" x="0" y="10"/><rect width="4" height="4" x="10" y="10"/><rect width="4" height="4" x="20" y="10"/><rect width="4" height="4" x="0" y="15"/><rect width="4" height="4" x="20" y="15"/><rect width="4" height="4" x="0" y="20"/><rect width="4" height="4" x="5" y="20"/><rect width="4" height="4" x="10" y="20"/><rect width="4" height="4" x="15" y="20"/><rect width="4" height="4" x="20" y="20"/></svg>',
                'cohere': '<svg class="inline-block w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10" fill="#39594D"/><text x="12" y="16" font-size="12" text-anchor="middle" fill="white" font-weight="bold">C</text></svg>',
                'meta': '<svg class="inline-block w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="#0467DF"/><circle cx="12" cy="12" r="5" fill="#0467DF"/></svg>',
                'smart_all': '<svg class="inline-block w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M9.5 3A6.5 6.5 0 0 1 16 9.5c0 1.61-.59 3.09-1.56 4.23l.27.27h.79l5 5-1.5 1.5-5-5v-.79l-.27-.27A6.516 6.516 0 0 1 9.5 16 6.5 6.5 0 0 1 3 9.5 6.5 6.5 0 0 1 9.5 3m0 2C7 5 5 7 5 9.5S7 14 9.5 14 14 12 14 9.5 12 5 9.5 5z" fill="#9333EA"/></svg>'
            };
            return icons[provider.toLowerCase()] || `<span class="inline-flex items-center justify-center w-5 h-5 bg-gray-200 text-gray-600 rounded text-xs font-bold">${provider.charAt(0).toUpperCase()}</span>`;
        }

        // ✅ Get provider display with icon
        function getProviderDisplay(provider, includeText = false) {
            const icon = getProviderIcon(provider);
            if (includeText) {
                return `<span class="flex items-center gap-2">${icon} <span>${provider.charAt(0).toUpperCase() + provider.slice(1)}</span></span>`;
            }
            return `<span class="flex items-center" title="${provider.charAt(0).toUpperCase() + provider.slice(1)}">${icon}</span>`;
        }

        // ✅ Extract provider from model name
        function getProviderFromModel(modelName) {
            const lowerModel = modelName.toLowerCase();

            // Map model prefixes to providers
            if (lowerModel.includes('gpt') || lowerModel.includes('o1') || lowerModel.includes('o3') || lowerModel.includes('openai')) return 'openai';
            if (lowerModel.includes('claude')) return 'claude';
            if (lowerModel.includes('gemini')) return 'gemini';
            if (lowerModel.includes('grok')) return 'grok';
            if (lowerModel.includes('mistral')) return 'mistral';
            if (lowerModel.includes('perplexity')) return 'perplexity';
            if (lowerModel.includes('cohere')) return 'cohere';
            if (lowerModel.includes('llama') || lowerModel.includes('meta')) return 'meta';

            // Fallback: use first part before dash
            return modelName.split('-')[0];
        }

        // ✅ Token restriction configuration
        let hasLowTokens = {{ $hasLowTokens ? 'true' : 'false' }}; // ✅ Changed from 'const' to 'let'
        const defaultModel = '{{ $defaultModel ?? '' }}';
        const userTokensLeft = {{ auth()->user()->tokens_left ?? 0 }};
        const defaultModelProvider = '{{ $defaultModel ? (App\Models\AISettings::where("openaimodel", $defaultModel)->first()->provider ?? "") : "" }}';

        console.log('Token restriction:', {
            active: hasLowTokens,
            tokensLeft: userTokensLeft,
            defaultModel: defaultModel,
            defaultProvider: defaultModelProvider
        });

        // ✅ ADD CREDIT VARIABLE
        const userCreditsLeft = {{ auth()->user()->credits_left ?? 0 }};

        console.log('User balance:', {
            tokensLeft: userTokensLeft,
            creditsLeft: userCreditsLeft
        });

        // ✅ Function to disable/enable controls based on tokens and credits
        function applyTokenCreditRestrictions() {
            const webSearchCheckbox = document.getElementById('web-search');
            const webSearchLabel = document.querySelector('label[for="web-search"]');
            const fileInput = document.getElementById('file-input');
            const attachmentLabel = document.querySelector('label[for="file-input"]');
            const createImageCheckbox = document.getElementById('create-image');
            const createImageLabel = document.getElementById('create-image-label');
            
            // ✅ Disable web search and attachments if tokens <= 0
            if (userTokensLeft <= 0) {
                // Disable web search
                if (webSearchCheckbox) {
                    webSearchCheckbox.disabled = true;
                    webSearchCheckbox.checked = false;
                    webSearchCheckbox.setAttribute('data-disabled-by-tokens', 'true');
                }
                if (webSearchLabel) {
                    webSearchLabel.style.opacity = '0.5';
                    webSearchLabel.style.cursor = 'not-allowed';
                    webSearchLabel.title = 'Insufficient tokens. Please recharge to use this feature.';
                }
                
                // Disable attachments
                if (fileInput) {
                    fileInput.disabled = true;
                    fileInput.setAttribute('data-disabled-by-tokens', 'true');
                }
                if (attachmentLabel) {
                    attachmentLabel.style.opacity = '0.5';
                    attachmentLabel.style.cursor = 'not-allowed';
                    attachmentLabel.title = 'Insufficient tokens. Please recharge to use this feature.';
                }
                
                console.log('Web search and attachments disabled due to insufficient tokens');
            }
            
            // ✅ Disable image generation if credits == 0
            if (userCreditsLeft === 0) {
                if (createImageCheckbox) {
                    createImageCheckbox.disabled = true;
                    createImageCheckbox.checked = false;
                    createImageCheckbox.setAttribute('data-disabled-by-credits', 'true');
                }
                if (createImageLabel) {
                    createImageLabel.style.opacity = '0.5';
                    createImageLabel.style.cursor = 'not-allowed';
                    createImageLabel.title = 'Insufficient credits. Please recharge to use this feature.';
                }
                
                console.log('Image generation disabled due to insufficient credits');
            }
        }

        // ✅ Prevent enabling through inspect element - Re-apply restrictions periodically
        setInterval(function() {
            const webSearchCheckbox = document.getElementById('web-search');
            const fileInput = document.getElementById('file-input');
            const createImageCheckbox = document.getElementById('create-image');
            
            // Re-disable if user tries to enable through inspect
            if (userTokensLeft <= 0) {
                if (webSearchCheckbox && !webSearchCheckbox.disabled) {
                    console.warn('User attempted to bypass token restriction on web search');
                    webSearchCheckbox.disabled = true;
                    webSearchCheckbox.checked = false;
                }
                if (fileInput && !fileInput.disabled) {
                    console.warn('User attempted to bypass token restriction on attachments');
                    fileInput.disabled = true;
                }
            }
            
            if (userCreditsLeft === 0) {
                if (createImageCheckbox && !createImageCheckbox.disabled) {
                    console.warn('User attempted to bypass credit restriction on image generation');
                    createImageCheckbox.disabled = true;
                    createImageCheckbox.checked = false;
                }
            }
        }, 1000); // Check every second
    </script>

    <script>
        // ✅ Get conversation ID from URL (passed from Laravel)
        const urlHexCode = '{{ $hexCode ?? '' }}';
        // Global variables
        window.debugMode = true;
        window.chartProcessingLog = [];
        let currentAttachmentFile = null; // ✅ NEW: Store current attachment file
        let imageGenModelMapping = {}; // ✅ CRITICAL: Initialize image generation model mapping

        function debugLog(message, data = null) {
            if (window.debugMode) {
                console.log('[CHAT DEBUG]', message, data || '');
                window.chartProcessingLog.push({
                    time: new Date().toISOString(),
                    message: message,
                    data: data
                });
            }
        }

        // Initialize variables
        let conversationHistory = {};
        let abortController = null;
        let selectedModels = [];
        let currentConversationId = null;
        let currentHexCode = null;
        let modelResponseElements = {};

        // ✅ NEW: Mapping between original model IDs and optimized model IDs
        let modelIdMapping = {}; // Maps: optimizedModel -> originalModel

        // Get DOM elements
        const compareForm = document.getElementById('compare-form');
        const messageInput = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');
        const stopButton = document.getElementById('stop-button');
        const modelsContainer = document.getElementById('models-container');
        const fileInput = document.getElementById('file-input');
        const attachmentPreview = document.getElementById('attachment-preview');
        let attachedFiles = [];
        const MAX_FILES = 10;
        const fileNameSpan = document.getElementById('file-name');
        const removeFileButton = document.getElementById('remove-file');
        const sidebar = document.getElementById('sidebar');
        const toggleSidebarButton = document.getElementById('toggle-sidebar');
        const closeSidebarButton = document.getElementById('close-sidebar');
        const newConversationButton = document.getElementById('new-conversation');
        const conversationsList = document.getElementById('conversations-list');
        const modelDropdownBtn = document.getElementById('model-dropdown-btn');
        const modelDropdown = document.getElementById('model-dropdown');
        const selectedCountSpan = document.getElementById('selected-count');
        const createImageCheckbox = document.getElementById('create-image');
        const createImageLabel = document.getElementById('create-image-label');
        const optionsDropdownBtn = document.getElementById('options-dropdown-btn');
        const optionsDropdown = document.getElementById('options-dropdown');

        // ✅ NEW: Preview modal elements
        const previewModal = document.getElementById('attachment-preview-modal');
        const closePreviewModalBtn = document.getElementById('close-preview-modal');
        const closePreviewModalFooterBtn = document.getElementById('close-preview-modal-btn');
        const previewContent = document.getElementById('preview-content');
        const previewModalTitle = document.getElementById('preview-modal-title');
        const previewModalFilename = document.getElementById('preview-modal-filename');
        const downloadAttachmentBtn = document.getElementById('download-attachment-btn');
        const previewAttachmentBtn = document.getElementById('preview-attachment-btn');

        // ✅ NEW: Enforce token-based restrictions on model selection
        function enforceTokenRestrictions() {
            if (!hasLowTokens || !defaultModel) {
                return; // No restrictions
            }
            
            console.log('🔒 Enforcing token restrictions for mode:', currentOptimizationMode);
            
            if (currentOptimizationMode === 'fixed') {
                // ✅ FIXED MODE: Disable all models except default
                document.querySelectorAll('.dropdown-model').forEach(modelDiv => {
                    const checkbox = modelDiv.querySelector('.model-checkbox');
                    if (!checkbox) return;
                    
                    const modelId = checkbox.value;
                    const isDefault = modelId === defaultModel;
                    
                    if (!isDefault) {
                        // Disable non-default models
                        checkbox.disabled = true;
                        checkbox.checked = false;
                        modelDiv.style.opacity = '0.4';
                        modelDiv.style.cursor = 'not-allowed';
                        modelDiv.title = 'Insufficient tokens - only default model available';
                        
                        // Prevent clicking
                        modelDiv.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            showNotification('⚠️ Low tokens: Only default model available', 'warning');
                        }, true);
                    } else {
                        // Enable and check default model
                        checkbox.disabled = false;
                        checkbox.checked = true;
                        modelDiv.style.opacity = '1';
                        modelDiv.style.cursor = 'pointer';
                        modelDiv.classList.add('bg-yellow-50', 'border-2', 'border-yellow-400');
                        modelDiv.title = 'Default model (required due to low tokens)';
                    }
                });
                
                // Also disable provider headers for non-default providers
                document.querySelectorAll('.dropdown-provider').forEach(providerDiv => {
                    const provider = providerDiv.dataset.provider;
                    if (provider !== defaultModelProvider) {
                        providerDiv.style.opacity = '0.4';
                    }
                });
                
            } else if (currentOptimizationMode === 'smart_same') {
                // ✅ SMART (SAME) MODE: Disable all providers except default model's provider
                document.querySelectorAll('.dropdown-model').forEach(modelDiv => {
                    const checkbox = modelDiv.querySelector('.provider-checkbox');
                    if (!checkbox) return;
                    
                    const provider = checkbox.value;
                    const isDefaultProvider = provider === defaultModelProvider;
                    
                    if (!isDefaultProvider) {
                        // Disable non-default providers
                        checkbox.disabled = true;
                        checkbox.checked = false;
                        modelDiv.style.opacity = '0.4';
                        modelDiv.style.cursor = 'not-allowed';
                        modelDiv.title = 'Insufficient tokens - only default provider available';
                        
                        // Prevent clicking
                        modelDiv.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            showNotification('⚠️ Low tokens: Only default provider available', 'warning');
                        }, true);
                    } else {
                        // Enable and check default provider
                        checkbox.disabled = false;
                        checkbox.checked = true;
                        modelDiv.style.opacity = '1';
                        modelDiv.style.cursor = 'pointer';
                        modelDiv.classList.add('bg-yellow-50', 'border-2', 'border-yellow-400');
                        modelDiv.title = 'Default provider (required due to low tokens)';
                    }
                });
            }
            // For smart_all mode, dropdown is already disabled, so no additional restrictions needed
            
            updateSelectedModels();
        }

        // ✅ NEW: Highlight default model in dropdown
        function highlightDefaultModel() {
            if (!hasLowTokens || !defaultModel) return;
            
            document.querySelectorAll('.dropdown-model').forEach(modelDiv => {
                const checkbox = modelDiv.querySelector('.model-checkbox');
                if (checkbox && checkbox.value === defaultModel) {
                    modelDiv.classList.add('default-model-highlight');
                }
            });
        }

        // ✅ Initialize provider icons in dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            // Add icons to provider headers in fixed mode dropdown
            document.querySelectorAll('.dropdown-provider').forEach(el => {
                const provider = el.dataset.provider;
                const iconWrapper = el.querySelector('.provider-icon-wrapper');
                if (iconWrapper && provider) {
                    iconWrapper.innerHTML = getProviderIcon(provider);
                }
            });

            // Add icons to provider checkboxes in smart mode dropdown
            document.querySelectorAll('.provider-checkbox').forEach(checkbox => {
                const provider = checkbox.dataset.provider;
                const wrapper = checkbox.closest('label').querySelector('.provider-icon-wrapper-smart');
                if (wrapper && provider) {
                    wrapper.innerHTML = getProviderIcon(provider);
                }
            });
        });

        // ✅ Apply restrictions on page load and add notification handlers
        document.addEventListener('DOMContentLoaded', function() {
            applyTokenCreditRestrictions();
            
            // Add click event listeners to show notification when clicking disabled controls
            const webSearchLabel = document.querySelector('label[for="web-search"]');
            const attachmentLabel = document.querySelector('label[for="file-input"]');
            const createImageLabel = document.getElementById('create-image-label');
            
            if (webSearchLabel && userTokensLeft <= 0) {
                webSearchLabel.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    showNotification('⚠️ Insufficient tokens. Please recharge to use web search.', 'error');
                });
            }
            
            if (attachmentLabel && userTokensLeft <= 0) {
                attachmentLabel.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    showNotification('⚠️ Insufficient tokens. Please recharge to upload attachments.', 'error');
                });
            }
            
            if (createImageLabel && userCreditsLeft === 0) {
                createImageLabel.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    showNotification('⚠️ Insufficient credits. Please recharge to generate images.', 'error');
                });
            }
        });

        // Call this after loading models
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                highlightDefaultModel();
                enforceTokenRestrictions();
            }, 500);
        });

        function isImageURL(str) {
            if (!str || typeof str !== 'string') return false;
            try {
                const url = new URL(str);
                const pathname = url.pathname.toLowerCase();
                const imageExtensions = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.bmp'];
                return imageExtensions.some(ext => pathname.endsWith(ext));
            } catch (e) {
                return false;
            }
        }
        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 200) + 'px';
        });

        // Options dropdown toggle
        optionsDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            optionsDropdown.classList.toggle('hidden');
            optionsDropdownBtn.classList.toggle('active');
        });

        // Check Chart.js loading
        debugLog('Chart.js status', typeof Chart !== 'undefined' ? 'LOADED' : 'FAILED');

        // ✅ NEW: Optimization Mode Management
        let currentOptimizationMode = 'fixed'; // default mode

        // Initialize optimization mode from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            // ✅ CHANGED: Check for conversation ID in URL first, then localStorage
            if (urlHexCode) {
                console.log('Loading conversation from URL:', urlHexCode);

                // Load the conversation
                loadConversation(urlHexCode);

                // Don't set optimization mode from localStorage when loading from URL
            } else {
                // ✅ Check if there's a current conversation in localStorage
                const savedHexCode = localStorage.getItem('multi_compare_current_hex_code');
                if (savedHexCode) {
                    console.log('Loading conversation from localStorage:', savedHexCode);
                    loadConversation(savedHexCode);
                } else {
                    // Only set mode when starting fresh (no URL conversation)
                    const savedMode = 'smart_all'; // Force Smart(All) mode on every page load
                    console.log('Starting fresh with mode:', savedMode);
                    setOptimizationMode(savedMode, true);
                }
            }

            // Load conversations list
            loadConversations();
        });

        // Optimization mode button handlers
        document.querySelectorAll('.optimization-mode-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const mode = this.dataset.mode;
                setOptimizationMode(mode);
            });
        });

        // ✅ NEW: Show custom modal for mode switch confirmation
        function showModeSwitchConfirmation(fromMode, toMode) {
            return new Promise((resolve) => {
                const modeNames = {
                    'fixed': 'Manual Select 🎯',
                    'smart_same': 'Auto-Match 🔄',
                    'smart_all': 'Auto-Best ✨'
                };
                
                const modal = document.createElement('div');
                modal.className = 'mode-switch-modal';
                modal.innerHTML = `
                    <div class="mode-switch-modal-content">
                        <div class="mode-switch-modal-header">
                            <div class="mode-switch-modal-icon">
                                <i class="las la-exchange-alt"></i>
                            </div>
                            <div class="mode-switch-modal-title">Switch Mode?</div>
                        </div>
                        <div class="mode-switch-modal-body">
                            <p>You're about to switch from <strong>${modeNames[fromMode]}</strong> to <strong>${modeNames[toMode]}</strong>.</p>
                            <p style="margin-top: 12px;">This will start a <strong>new conversation</strong>. Your current chat will be saved in history.</p>
                            <div class="mode-switch-modal-info">
                                <i class="las la-info-circle"></i> You can access your previous conversation anytime from the sidebar.
                            </div>
                        </div>
                        <div class="mode-switch-modal-actions">
                            <button class="mode-switch-modal-btn mode-switch-modal-btn-cancel">
                                Cancel
                            </button>
                            <button class="mode-switch-modal-btn mode-switch-modal-btn-confirm">
                                <i class="las la-check"></i> Start New Conversation
                            </button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Fade in animation
                setTimeout(() => {
                    modal.style.opacity = '1';
                }, 10);
                
                // Handle cancel
                modal.querySelector('.mode-switch-modal-btn-cancel').addEventListener('click', () => {
                    modal.style.opacity = '0';
                    setTimeout(() => {
                        modal.remove();
                        resolve(false);
                    }, 200);
                });
                
                // Handle confirm
                modal.querySelector('.mode-switch-modal-btn-confirm').addEventListener('click', () => {
                    modal.style.opacity = '0';
                    setTimeout(() => {
                        modal.remove();
                        resolve(true);
                    }, 200);
                });
                
                // Handle backdrop click
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.style.opacity = '0';
                        setTimeout(() => {
                            modal.remove();
                            resolve(false);
                        }, 200);
                    }
                });
            });
        }

        // ✅ ENHANCED: setOptimizationMode function with skipAutoSelection parameter
        async function setOptimizationMode(mode, skipConfirmation = false, skipAutoSelection = false) {
            const previousMode = currentOptimizationMode;
            const hasExistingConversation = currentConversationId !== null || 
                                            Object.values(conversationHistory).some(history => history.length > 0);
            
            // ✅ Check token restrictions
            if (hasLowTokens && !skipConfirmation) {
                if (mode === 'smart_all') {
                    showNotification(`⚠️ Low tokens (${userTokensLeft.toLocaleString()}). Auto-Best mode will only use ${defaultModel}.`, 'warning');
                } else if (mode === 'smart_same') {
                    showNotification(`⚠️ Low tokens (${userTokensLeft.toLocaleString()}). Only ${defaultModelProvider} provider available.`, 'warning');
                } else if (mode === 'fixed') {
                    showNotification(`⚠️ Low tokens (${userTokensLeft.toLocaleString()}). Only ${defaultModel} available.`, 'warning');
                }
            }
            
            // Confirmation logic
            if (!skipConfirmation && previousMode !== mode && hasExistingConversation) {
                const confirmed = await showModeSwitchConfirmation(previousMode, mode);
                if (!confirmed) {
                    document.querySelectorAll('.optimization-mode-btn').forEach(btn => {
                        if (btn.dataset.mode === previousMode) {
                            btn.classList.add('active');
                        } else {
                            btn.classList.remove('active');
                        }
                    });
                    return;
                }
            }
            
            currentOptimizationMode = mode;
            localStorage.setItem('multi_compare_optimization_mode', mode);
            
            // Update button states
            document.querySelectorAll('.optimization-mode-btn').forEach(btn => {
                if (btn.dataset.mode === mode) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            
            // Clear conversation only when manually switching
            if (!skipConfirmation && hasExistingConversation) {
                currentConversationId = null;
                currentHexCode = null;
                // ✅ Clear localStorage when switching modes
                localStorage.removeItem('multi_compare_current_hex_code');
                conversationHistory = {};
                selectedModels.forEach(model => {
                    conversationHistory[model.model] = [];
                });
                window.history.pushState({}, '', '{{ route('chat.compare') }}');
            }
            
            const modelDropdownBtn = document.getElementById('model-dropdown-btn');
            const fixedDropdown = document.getElementById('model-dropdown-fixed');
            const smartDropdown = document.getElementById('model-dropdown-smart');
            
            // ✅ Reset all restrictions first
            document.querySelectorAll('.dropdown-model').forEach(modelDiv => {
                const checkbox = modelDiv.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.disabled = false;
                }
                modelDiv.style.opacity = '1';
                modelDiv.style.cursor = 'pointer';
                modelDiv.classList.remove('bg-yellow-50', 'border-2', 'border-yellow-400');
                modelDiv.title = '';
            });
            
            document.querySelectorAll('.dropdown-provider').forEach(providerDiv => {
                providerDiv.style.opacity = '1';
            });
            
            if (mode === 'fixed') {
                modelDropdownBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'hidden'); // ✅ Show button
                modelDropdownBtn.disabled = false;
                fixedDropdown.classList.remove('hidden');
                smartDropdown.classList.add('hidden');
                
                if (!skipAutoSelection) {
                    document.querySelectorAll('.model-checkbox').forEach(cb => cb.checked = false);
                    document.querySelectorAll('.provider-checkbox').forEach(cb => cb.checked = false);
                    
                    if (hasLowTokens && defaultModel) {
                        // Select only the default model
                        const defaultCheckbox = document.querySelector(`input.model-checkbox[value="${defaultModel}"]`);
                        if (defaultCheckbox) {
                            defaultCheckbox.checked = true;
                            console.log('✅ Low tokens: Auto-selected default model:', defaultModel);
                        }
                    } else {
                        // Normal behavior: select cheapest model
                        const cheapestModel = findCheapestModel();
                        if (cheapestModel) {
                            const checkbox = document.querySelector(`input.model-checkbox[value="${cheapestModel}"]`);
                            if (checkbox) {
                                checkbox.checked = true;
                                console.log('✅ Auto-selected cheapest model:', cheapestModel);
                            }
                        }
                    }
                }
                
                // ✅ Apply restrictions after setting up
                setTimeout(() => enforceTokenRestrictions(), 100);
                updateSelectedModels();
                
            } else if (mode === 'smart_same') {
                modelDropdownBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'hidden'); // ✅ Show button
                modelDropdownBtn.disabled = false;
                fixedDropdown.classList.add('hidden');
                smartDropdown.classList.remove('hidden');
                
                if (!skipAutoSelection) {
                    document.querySelectorAll('.model-checkbox').forEach(cb => cb.checked = false);
                    document.querySelectorAll('.provider-checkbox').forEach(cb => {
                        if (hasLowTokens && defaultModel) {
                            // Only check the provider that has the default model
                            cb.checked = (cb.value === defaultModelProvider);
                        } else {
                            cb.checked = true;
                        }
                    });
                    console.log('✅ Smart (Same) mode providers selected');
                }
                
                // ✅ Apply restrictions after setting up
                setTimeout(() => enforceTokenRestrictions(), 100);
                updateSelectedModels();
                
            } else if (mode === 'smart_all') {
                modelDropdownBtn.classList.add('hidden'); // ✅ Hide completely in Auto-Best mode
                modelDropdownBtn.disabled = true;
                fixedDropdown.classList.add('hidden');
                smartDropdown.classList.add('hidden');
                document.querySelectorAll('.model-checkbox').forEach(cb => cb.checked = false);
                document.querySelectorAll('.provider-checkbox').forEach(cb => cb.checked = false);
                
                const isLoadingConversation = skipConfirmation && selectedModels.length > 0 && selectedModels[0].model === 'smart_all_auto';
                
                if (!isLoadingConversation) {
                    const displayName = hasLowTokens ? `Smart Mode (${defaultModel} only)` : 'Smart Mode';
                    selectedModels = [{
                        model: 'smart_all_auto',
                        provider: 'smart_all',
                        displayName: displayName
                    }];
                    conversationHistory['smart_all_auto'] = [];
                    
                    generateModelPanels();
                }
            }
            
            // Show notification only when manually switching
            if (!skipConfirmation) {
                const modeNames = {
                    'fixed': 'Manual Select',
                    'smart_same': 'Auto-Match',
                    'smart_all': 'Auto-Best'
                };
                
                const tokenWarning = hasLowTokens ? ' ⚠️ Restricted due to low tokens' : '';
                showNotification(`✓ Switched to ${modeNames[mode]}${tokenWarning}`, hasLowTokens ? 'warning' : 'success');
            }
            
            console.log('Optimization mode set to:', mode, skipConfirmation ? '(loading/initial)' : '(manual switch)');
        }

        // ✅ FIXED: Helper function to find the cheapest model with proper numeric handling
        function findCheapestModel() {
            let cheapestModel = null;
            let lowestCost = Infinity;
            
            // Collect all models with their costs for debugging
            const modelCosts = [];
            
            console.log('🔍 Starting search for cheapest model...');
            console.log('Total checkboxes found:', document.querySelectorAll('input.model-checkbox').length);
            
            // Check all available model checkboxes and their cost data
            document.querySelectorAll('input.model-checkbox').forEach((checkbox, index) => {
                const modelId = checkbox.value;
                const costStr = checkbox.getAttribute('data-cost'); // Use getAttribute to be explicit
                const displayName = checkbox.getAttribute('data-display-name');
                
                // Parse cost with extra validation
                let cost = parseFloat(costStr);
                
                // Handle potential parsing issues
                if (costStr === null || costStr === undefined || costStr === '') {
                    console.warn(`  ⚠️ [${index}] ${displayName}: No cost attribute found`);
                    cost = 999999;
                }
                
                console.log(`  [${index}] ${displayName} (${modelId})`);
                console.log(`      Raw cost string: "${costStr}"`);
                console.log(`      Parsed cost: ${cost}`);
                console.log(`      Current lowest: ${lowestCost}`);
                
                // Log each model's cost
                modelCosts.push({
                    index: index,
                    modelId: modelId,
                    displayName: displayName,
                    costStr: costStr,
                    cost: cost,
                    isValid: !isNaN(cost) && cost !== null && cost !== undefined
                });
                
                // Only consider valid costs
                if (!isNaN(cost) && cost !== null && cost !== undefined && cost !== Infinity) {
                    if (cost < lowestCost) {
                        console.log(`      ✅ NEW CHEAPEST! ${cost} < ${lowestCost}`);
                        lowestCost = cost;
                        cheapestModel = modelId;
                    } else {
                        console.log(`      ❌ Not cheaper: ${cost} >= ${lowestCost}`);
                    }
                } else {
                    console.log(`      ⚠️ INVALID cost, skipping`);
                }
                console.log('---');
            });
            
            // Sort by cost for easy viewing
            modelCosts.sort((a, b) => {
                const costA = isNaN(a.cost) ? 999999 : a.cost;
                const costB = isNaN(b.cost) ? 999999 : b.cost;
                return costA - costB;
            });
            
            console.log('📊 All models sorted by cost (cheapest first):');
            console.table(modelCosts.map(m => ({
                'Model': m.displayName,
                'ID': m.modelId,
                'Cost': m.cost,
                'Valid': m.isValid
            })));
            
            console.log('✅ FINAL SELECTION:');
            console.log(`   Model: ${cheapestModel}`);
            console.log(`   Cost: ${lowestCost}`);
            
            // Fallback: if no cost data found, just pick the first model
            if (!cheapestModel || lowestCost === Infinity) {
                console.warn('⚠️ No valid cheapest model found, using fallback...');
                const firstCheckbox = document.querySelector('input.model-checkbox');
                if (firstCheckbox) {
                    cheapestModel = firstCheckbox.value;
                    console.warn(`   Using first model: ${cheapestModel}`);
                }
            }
            
            return cheapestModel;
        }
        // ✅ NEW: Transfer model selections to provider selections
        function transferSelectionsToProviders() {
            const selectedProviders = new Set();
            
            // Find which providers have selected models
            document.querySelectorAll('.model-checkbox:checked').forEach(checkbox => {
                selectedProviders.add(checkbox.dataset.provider);
            });
            
            // Check the corresponding provider checkboxes
            document.querySelectorAll('.provider-checkbox').forEach(checkbox => {
                checkbox.checked = selectedProviders.has(checkbox.dataset.provider);
            });
        }

        // Model dropdown functionality
        modelDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            
            // ✅ Don't open if disabled (Smart All mode)
            if (modelDropdownBtn.disabled) {
                return;
            }
            
            // Toggle the appropriate dropdown based on mode
            if (currentOptimizationMode === 'fixed') {
                const dropdown = document.getElementById('model-dropdown-fixed');
                dropdown.classList.toggle('show');
                
                // ✅ Re-enforce restrictions when opening dropdown
                if (dropdown.classList.contains('show')) {
                    setTimeout(() => enforceTokenRestrictions(), 50);
                }
            } else if (currentOptimizationMode === 'smart_same') {
                const dropdown = document.getElementById('model-dropdown-smart');
                dropdown.classList.toggle('show');
                
                // ✅ Re-enforce restrictions when opening dropdown
                if (dropdown.classList.contains('show')) {
                    setTimeout(() => enforceTokenRestrictions(), 50);
                }
            }
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!optionsDropdownBtn.contains(e.target) && !optionsDropdown.contains(e.target)) {
                optionsDropdown.classList.add('hidden');
                optionsDropdownBtn.classList.remove('active');
            }
            
            if (!modelDropdownBtn.contains(e.target) && 
                !document.getElementById('model-dropdown-fixed').contains(e.target) &&
                !document.getElementById('model-dropdown-smart').contains(e.target)) {
                document.getElementById('model-dropdown-fixed').classList.remove('show');
                document.getElementById('model-dropdown-smart').classList.remove('show');
            }
        });

        async function toggleArchiveConversation(hexCode, currentlyArchived) {
            const action = currentlyArchived ? 'unarchive' : 'archive';
            
            try {
                const response = await fetch(`{{ url('/toggle-archive-multi-compare-conversation') }}/${hexCode}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to ' + action + ' conversation');
                }
                
                const data = await response.json();
                showNotification(data.message, 'success');
                loadConversations();
                
            } catch (error) {
                console.error('Archive error:', error);
                showNotification('Failed to ' + action + ' conversation', 'error');
            }
        }

        // Sidebar functionality
        const mainContent = document.getElementById('main-content');

        toggleSidebarButton.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-hidden');
            sidebar.classList.toggle('sidebar-visible');
            mainContent.classList.toggle('main-content-normal');
            mainContent.classList.toggle('main-content-shifted');
        });

        closeSidebarButton.addEventListener('click', () => {
            sidebar.classList.add('sidebar-hidden');
            sidebar.classList.remove('sidebar-visible');
            mainContent.classList.add('main-content-normal');
            mainContent.classList.remove('main-content-shifted');
        });

        newConversationButton.addEventListener('click', () => {
            const baseUrl = '{{ route('chat.compare') }}';
            window.history.pushState({ hexCode: null }, '', baseUrl);
            console.log('✅ URL reset to base route');

            currentHexCode = null;
            // ✅ Clear localStorage when starting a new conversation
            localStorage.removeItem('multi_compare_current_hex_code');
            selectedModels.forEach(model => {
                conversationHistory[model.model] = [];
            });
            regenerateModelPanels();
            
            if (hasLowTokens) {
                enforceTokenRestrictions();
                showNotification('⚠️ Low tokens: Only default model available for new conversations', 'warning');
            }

            sidebar.classList.add('sidebar-hidden');
            sidebar.classList.remove('sidebar-visible');
            mainContent.classList.add('main-content-normal');
            mainContent.classList.remove('main-content-shifted');
        });

        // Model and Provider selection handling
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('model-checkbox') || e.target.classList.contains('provider-checkbox')) {
                // ✅ Prevent deselecting default model/provider when tokens are low
                if (hasLowTokens) {
                    if (e.target.classList.contains('model-checkbox') && e.target.value === defaultModel) {
                        if (!e.target.checked) {
                            e.preventDefault();
                            e.target.checked = true;
                            showNotification('⚠️ Default model cannot be deselected (low tokens)', 'warning');
                            return;
                        }
                    }
                    
                    if (e.target.classList.contains('provider-checkbox') && e.target.value === defaultModelProvider) {
                        if (!e.target.checked) {
                            e.preventDefault();
                            e.target.checked = true;
                            showNotification('⚠️ Default provider cannot be deselected (low tokens)', 'warning');
                            return;
                        }
                    }
                }
                
                updateSelectedModels();
            }
        });

        // ✅ Handle browser back/forward buttons
        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.hexCode) {
                console.log('Loading conversation from browser history:', event.state.hexCode);
                loadConversation(event.state.hexCode);
            } else {
                console.log('Going back to new conversation');
                currentHexCode = null;
                // ✅ Clear localStorage when going back to new conversation
                localStorage.removeItem('multi_compare_current_hex_code');
                selectedModels.forEach(model => {
                    conversationHistory[model.model] = [];
                });
                regenerateModelPanels();
            }
        });

        /**
         * Get provider for a model from DOM data attributes
         * @param {string} modelId - The model identifier
         * @returns {string} Provider name or null
         */
        function getModelProviderFromDOM(modelId) {
            // Try model checkbox first (Fixed mode)
            const modelCheckbox = document.querySelector(`input.model-checkbox[value="${modelId}"]`);
            if (modelCheckbox && modelCheckbox.dataset.provider) {
                return modelCheckbox.dataset.provider;
            }
            
            // Try provider checkbox (Smart modes)
            const providerCheckbox = document.querySelector(`input.provider-checkbox[value="${modelId}"]`);
            if (providerCheckbox && providerCheckbox.dataset.provider) {
                return providerCheckbox.dataset.provider;
            }
            
            // Fallback: Try to extract from panel ID (for smart_same mode)
            if (modelId.includes('_smart_panel')) {
                return modelId.replace('_smart_panel', '');
            }
            
            return null;
        }

        function updateSelectedModels() {
            if (currentOptimizationMode === 'fixed') {
                // ✅ Fixed mode: use model checkboxes
                const checkboxes = document.querySelectorAll('.model-checkbox:checked');
                selectedModels = Array.from(checkboxes).map(cb => ({
                    model: cb.value,
                    provider: cb.dataset.provider,
                    displayName: cb.dataset.displayName
                }));
            } else if (currentOptimizationMode === 'smart_all') {
                // ✅ NEW: Smart (All) mode - preserve existing panel if it exists
                if (selectedModels.length === 0 || selectedModels[0].model !== 'smart_all_auto') {
                    const displayName = hasLowTokens ? `Smart Mode (${defaultModel} only)` : 'Smart Mode';
                    selectedModels = [{
                        model: 'smart_all_auto',
                        provider: 'smart_all',
                        displayName: displayName
                    }];
                }
                // Don't regenerate panels - they're already set up correctly
                
                // Update count display
                selectedCountSpan.textContent = '1 Model';
                return; // ✅ IMPORTANT: Exit early to prevent panel regeneration
            } else {
                // ✅ Smart (Same) mode - use STABLE panel IDs with provider name
                const providerCheckboxes = document.querySelectorAll('.provider-checkbox:checked');
                selectedModels = Array.from(providerCheckboxes).map(cb => ({
                    model: `${cb.dataset.provider}_smart_panel`,
                    provider: cb.dataset.provider,
                    displayName: `${cb.dataset.provider.charAt(0).toUpperCase() + cb.dataset.provider.slice(1)} (Smart)`
                }));
            }

            // Update count display
            const countText = selectedModels.length === 0 ? '0 Models' : 
                selectedModels.length === 1 ? '1 Model' : `${selectedModels.length} Models`;
            
            selectedCountSpan.textContent = currentOptimizationMode === 'fixed' 
                ? countText 
                : (selectedModels.length === 0 ? '0 Providers' : 
                selectedModels.length === 1 ? '1 Provider' : `${selectedModels.length} Providers`);


            // Update dropdown selection highlights
            if (currentOptimizationMode === 'fixed') {
                document.querySelectorAll('.dropdown-model').forEach(item => {
                    if (item.dataset.model) {
                        const isSelected = selectedModels.some(m => m.model === item.dataset.model);
                        item.classList.toggle('selected', isSelected);
                    }
                });
            } else {
                document.querySelectorAll('#model-dropdown-smart .dropdown-model').forEach(item => {
                    const isSelected = selectedModels.some(m => m.provider === item.dataset.provider);
                    item.classList.toggle('selected', isSelected);
                });
            }

            // ====== MODEL SEARCH FUNCTIONALITY (Fixed Mode Only) ======
            const modelSearchInput = document.getElementById('model-search-input');
            const clearModelSearchBtn = document.getElementById('clear-model-search');
            const noModelResults = document.getElementById('no-model-results');
            const modelSearchCount = document.getElementById('model-search-count');
            const modelMatchCount = document.getElementById('model-match-count');
            const modelsListContainer = document.getElementById('models-list-container');

            // Search models in Fixed mode dropdown
            if (modelSearchInput) {
                modelSearchInput.addEventListener('input', function(e) {
                    e.stopPropagation(); // Prevent dropdown from closing
                    const searchTerm = this.value.toLowerCase().trim();
                    
                    // Show/hide clear button
                    if (searchTerm) {
                        clearModelSearchBtn.classList.remove('hidden');
                    } else {
                        clearModelSearchBtn.classList.add('hidden');
                    }
                    
                    filterModels(searchTerm);
                });
                
                // Prevent dropdown from closing when clicking in search input
                modelSearchInput.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
                
                // Clear search
                clearModelSearchBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    modelSearchInput.value = '';
                    clearModelSearchBtn.classList.add('hidden');
                    filterModels('');
                    modelSearchInput.focus();
                });
            }

            // Filter models based on search term
            function filterModels(searchTerm) {
                const allModels = document.querySelectorAll('#model-dropdown-fixed .dropdown-model');
                const allProviders = document.querySelectorAll('#model-dropdown-fixed .dropdown-provider');
                let visibleCount = 0;
                let currentProvider = null;
                let providerHasVisibleModels = false;
                
                if (!searchTerm) {
                    // Show all models and providers
                    allModels.forEach(model => {
                        model.style.display = '';
                    });
                    allProviders.forEach(provider => {
                        provider.style.display = '';
                    });
                    noModelResults.classList.add('hidden');
                    modelsListContainer.classList.remove('hidden');
                    modelSearchCount.classList.add('hidden');
                    return;
                }
                
                // Hide all providers initially
                allProviders.forEach(provider => {
                    provider.style.display = 'none';
                });
                
                // Filter models
                allModels.forEach(model => {
                    const searchText = model.dataset.searchText || '';
                    const provider = model.dataset.provider;
                    
                    if (searchText.includes(searchTerm)) {
                        model.style.display = '';
                        visibleCount++;
                        
                        // Show the provider header for this model
                        const providerHeader = document.querySelector(`#model-dropdown-fixed .dropdown-provider[data-provider="${provider}"]`);
                        if (providerHeader) {
                            providerHeader.style.display = '';
                        }
                    } else {
                        model.style.display = 'none';
                    }
                });
                
                // Update UI based on results
                if (visibleCount === 0) {
                    noModelResults.classList.remove('hidden');
                    modelsListContainer.classList.add('hidden');
                    modelSearchCount.classList.add('hidden');
                } else {
                    noModelResults.classList.add('hidden');
                    modelsListContainer.classList.remove('hidden');
                    modelSearchCount.classList.remove('hidden');
                    modelMatchCount.textContent = visibleCount;
                }
            }

            // Reset search when dropdown closes
            const originalModelDropdownToggle = modelDropdownBtn.onclick;
            modelDropdownBtn.addEventListener('click', (e) => {
                const dropdown = document.getElementById('model-dropdown-fixed');
                
                // If dropdown is closing (will be hidden after toggle)
                if (dropdown.classList.contains('show')) {
                    // Reset search when closing
                    if (modelSearchInput) {
                        modelSearchInput.value = '';
                        clearModelSearchBtn.classList.add('hidden');
                        filterModels('');
                    }
                } else {
                    // Focus search input when opening
                    setTimeout(() => {
                        if (currentOptimizationMode === 'fixed' && modelSearchInput) {
                            modelSearchInput.focus();
                        }
                    }, 100);
                }
            });

            // Also reset search when clicking outside dropdown
            document.addEventListener('click', (e) => {
                const fixedDropdown = document.getElementById('model-dropdown-fixed');
                
                if (!modelDropdownBtn.contains(e.target) && 
                    !fixedDropdown.contains(e.target) &&
                    fixedDropdown.classList.contains('show')) {
                    
                    // Reset search
                    if (modelSearchInput) {
                        modelSearchInput.value = '';
                        clearModelSearchBtn.classList.add('hidden');
                        filterModels('');
                    }
                }
            });

            // ✅ NEW: Check web search support
            const supportsWebSearch = selectedModels.some(m => {
                // Get the checkbox for this model
                const checkbox = document.querySelector(`input.model-checkbox[value="${m.model}"]`);
                if (checkbox) {
                    return checkbox.dataset.supportsWebSearch === '1';
                }
                
                // For smart modes, check provider checkboxes
                const providerCheckbox = document.querySelector(`input.provider-checkbox[value="${m.provider}"]`);
                if (providerCheckbox) {
                    // Check if any model in this provider supports web search
                    const providerModels = document.querySelectorAll(`input.model-checkbox[data-provider="${m.provider}"]`);
                    return Array.from(providerModels).some(cb => cb.dataset.supportsWebSearch === '1');
                }
                
                return false;
            });
            
            // ✅ Enable/disable web search option
            const webSearchLabel = document.querySelector('label[for="web-search"]');
            const webSearchCheckbox = document.getElementById('web-search');
            
            if (webSearchLabel && webSearchCheckbox) {
                if (supportsWebSearch) {
                    webSearchLabel.classList.remove('opacity-50', 'cursor-not-allowed');
                    webSearchLabel.title = 'Search the web for current information';
                    webSearchCheckbox.disabled = false;
                } else {
                    webSearchLabel.classList.add('opacity-50', 'cursor-not-allowed');
                    webSearchLabel.title = 'No selected models support web search';
                    webSearchCheckbox.disabled = true;
                    webSearchCheckbox.checked = false;
                }
            }

            // Check image generation support - all providers except Claude
            const supportsImageGen = selectedModels.some(m => {
                const provider = getModelProviderFromDOM(m.model) || m.provider;
                return provider !== 'claude';
            });
            
            if (createImageLabel) {
                if (supportsImageGen) {
                    createImageLabel.classList.remove('opacity-50', 'cursor-not-allowed');
                    createImageLabel.title = 'Generate images (supported models will create images)';
                    createImageCheckbox.disabled = false;
                } else {
                    createImageLabel.classList.add('opacity-50', 'cursor-not-allowed');
                    createImageLabel.title = 'No selected models support image generation';
                    createImageCheckbox.disabled = true;
                    createImageCheckbox.checked = false;
                }
            }

            generateModelPanels();
        }

        function modelSupportsImageGen(model) {
            // Get provider from the model's data attribute
            const checkbox = document.querySelector(`input.model-checkbox[value="${model}"]`);
            
            if (checkbox && checkbox.dataset.provider) {
                const provider = checkbox.dataset.provider;
                // All providers support image generation except Claude
                return provider !== 'claude';
            }
            
            // Fallback: Check provider checkbox (for smart modes)
            const providerCheckbox = document.querySelector(`input.provider-checkbox[value="${model}"]`);
            if (providerCheckbox && providerCheckbox.dataset.provider) {
                const provider = providerCheckbox.dataset.provider;
                return provider !== 'claude';
            }
            
            // Last resort fallback: hardcoded check (backwards compatibility)
            const modelLower = model.toLowerCase();
            return !modelLower.includes('claude');
        }

        function generateModelPanels() {
            if (selectedModels.length === 0) {
                modelsContainer.innerHTML = `
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center text-white/60">
                            <i class="las la-robot text-6xl mb-4"></i>
                            <p class="text-lg">Select models from the dropdown above to start comparing</p>
                        </div>
                    </div>
                `;
                modelsContainer.className = 'flex-1 grid gap-4 mb-4 overflow-auto';
                return;
            }

            let gridClass = 'models-grid-1';
            if (selectedModels.length === 2) gridClass = 'models-grid-2';
            else if (selectedModels.length === 3) gridClass = 'models-grid-3';
            else if (selectedModels.length >= 4) gridClass = 'models-grid-4';
            
            modelsContainer.className = `flex-1 grid gap-3 mb-3 overflow-auto ${gridClass}`;

            modelsContainer.innerHTML = selectedModels.map(model => `
                <div class="bg-white rounded-lg shadow model-panel border border-gray-200" data-model-id="${model.model}">
                    <div class="model-panel-header">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded flex items-center justify-center text-sm">
                                ${getProviderIcon(model.provider)}
                            </div>
                            <span class="font-semibold text-gray-900 text-sm">${model.displayName}</span>
                            <span id="status-${model.model}" class="model-status ml-auto text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Ready</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button class="maximize-model-btn text-gray-500 hover:text-indigo-600 transition-colors p-1"
                                    data-model="${model.model}"
                                    title="Maximize">
                                <i class="las la-expand"></i>
                            </button>
                            ${currentOptimizationMode !== 'smart_all' ? `
                                <button class="close-model-btn text-gray-500 hover:text-red-600 transition-colors p-1"
                                        data-model="${model.model}"
                                        title="Close">
                                    <i class="las la-times"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    <div class="model-response">
                        <div id="conversation-${model.model}" class="model-conversation">
                            ${getConversationHTML(model.model)}
                        </div>
                    </div>
                </div>
            `).join('');

            // ✅ CRITICAL: Initialize modelResponseElements for ALL models
            selectedModels.forEach(model => {
                if (!conversationHistory[model.model]) {
                    conversationHistory[model.model] = [];
                }
                // Initialize as null, will be set when message is added
                modelResponseElements[model.model] = null;
            });
            
            console.log('✅ Model panels generated', {
                models: selectedModels.map(m => m.model),
                responseElements: Object.keys(modelResponseElements)
            });
            
            // Process any content that needs formatting
            setTimeout(() => {
                processUnprocessedContent();
            }, 100);
        }

        function processUnprocessedContent() {
            // Find all message content that needs processing
            document.querySelectorAll('[data-needs-processing="true"]').forEach(element => {
                element.removeAttribute('data-needs-processing');
                
                // Check if it's an image URL
                const content = element.textContent;
                if (isImageURL(content)) {
                    element.innerHTML = '';
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'my-4';
                    
                    const img = document.createElement('img');
                    img.src = content;
                    img.alt = 'Generated image';
                    img.className = 'rounded-lg max-w-full h-auto shadow-md cursor-pointer';
                    img.addEventListener('click', () => {
                        openImageModal(content, 'Generated image');
                    });
                    
                    imgContainer.appendChild(img);
                    element.appendChild(imgContainer);
                } else {
                    // Process markdown and formatting
                    processMessageContent(element);
                }
            });
        }

        function regenerateModelPanels() {
            selectedModels.forEach(model => {
                const conversationDiv = document.getElementById(`conversation-${model.model}`);
                if (conversationDiv) {
                    conversationDiv.innerHTML = getConversationHTML(model.model);
                }
            });
        }

        function getConversationHTML(modelId) {
            const history = conversationHistory[modelId] || [];
            if (history.length === 0) {
                return getEmptyStateHTML();
            }

            return history.map((entry, index) => {
                const responseId = `processed-response-${modelId}-${index}`;
                const userMsgId = `user-msg-${modelId}-${index}`;
                const assistantMsgId = `assistant-msg-${modelId}-${index}`;
                
                return `
                    <div class="conversation-entry">
                        <div class="user-prompt" data-message-id="${userMsgId}">
                            ${formatUserMessage(entry.prompt)}
                        </div>
                        <div class="message-actions">
                            <button class="message-action-btn copy-msg-btn" data-message-id="${userMsgId}" title="Copy Prompt">
                                <i class="las la-copy"></i>
                            </button>
                            <button class="message-action-btn read-msg-btn" data-message-id="${userMsgId}" title="Read Aloud">
                                <i class="las la-volume-up"></i>
                            </button>
                            <button class="message-action-btn translate-msg-btn" data-message-id="${userMsgId}" title="Translate Prompt">
                                <i class="las la-language"></i>
                            </button>
                        </div>
                        <div class="assistant-response" data-message-id="${assistantMsgId}" data-model="${modelId}">
                            <div class="message-content" id="${responseId}">${
                                isImageURL(entry.response)
                                    ? `<div class="my-4"><img src="${escapeHtml(entry.response)}" alt="Generated image" class="rounded-lg max-w-full h-auto shadow-md cursor-pointer" onclick="openImageModal('${escapeHtml(entry.response)}', 'Generated image')"></div>`
                                    : `<span data-needs-processing="true">${escapeHtml(entry.response)}</span>`
                            }</div>
                            <div class="message-actions">
                                <button class="message-action-btn copy-msg-btn" data-message-id="${assistantMsgId}" title="Copy Response">
                                    <i class="las la-copy"></i>
                                </button>
                                <button class="message-action-btn read-msg-btn" data-message-id="${assistantMsgId}" title="Read Aloud">
                                    <i class="las la-volume-up"></i>
                                </button>
                                <button class="message-action-btn regenerate-msg-btn" data-message-id="${assistantMsgId}" data-model="${modelId}" title="Regenerate Response">
                                    <i class="las la-redo-alt"></i>
                                </button>
                                <button class="message-action-btn translate-msg-btn" data-message-id="${assistantMsgId}" title="Translate Response">
                                    <i class="las la-language"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // ✅ NEW: Message action handlers
        document.addEventListener('click', async (e) => {
            // Copy message
            if (e.target.closest('.copy-msg-btn')) {
                const btn = e.target.closest('.copy-msg-btn');
                const messageId = btn.dataset.messageId;
                const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                
                if (messageElement) {
                    const content = messageElement.querySelector('.message-content')?.textContent || messageElement.textContent;
                    try {
                        await navigator.clipboard.writeText(content.trim());
                        const originalHTML = btn.innerHTML;
                        btn.innerHTML = '<i class="las la-check"></i> Copied!';
                        btn.classList.add('active');
                        setTimeout(() => {
                            btn.innerHTML = originalHTML;
                            btn.classList.remove('active');
                        }, 2000);
                    } catch (err) {
                        console.error('Failed to copy:', err);
                    }
                }
                return;
            }
            
            // Read aloud message
            if (e.target.closest('.read-msg-btn')) {
                const btn = e.target.closest('.read-msg-btn');
                const messageId = btn.dataset.messageId;
                const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                
                if (messageElement) {
                    const content = messageElement.querySelector('.message-content')?.textContent || messageElement.textContent;
                    
                    if (window.speechSynthesis.speaking) {
                        window.speechSynthesis.cancel();
                        btn.innerHTML = '<i class="las la-volume-up"></i> Read';
                        btn.classList.remove('active');
                        return;
                    }
                    
                    const speech = new SpeechSynthesisUtterance(content.trim());
                    speech.rate = 1;
                    speech.pitch = 1;
                    speech.volume = 1;
                    
                    btn.innerHTML = '<i class="las la-stop"></i> Stop';
                    btn.classList.add('active');
                    
                    speech.onend = () => {
                        btn.innerHTML = '<i class="las la-volume-up"></i> Read';
                        btn.classList.remove('active');
                    };
                    
                    window.speechSynthesis.speak(speech);
                }
                return;
            }
            
            // Regenerate message
            if (e.target.closest('.regenerate-msg-btn')) {
                const btn = e.target.closest('.regenerate-msg-btn');
                const messageId = btn.dataset.messageId;
                const modelId = btn.dataset.model;
                
                await regenerateMessage(modelId, messageId, btn);
                return;
            }
            
            // Translate message
            if (e.target.closest('.translate-msg-btn')) {
                const btn = e.target.closest('.translate-msg-btn');
                const messageId = btn.dataset.messageId;
                
                showTranslateDropdown(btn, messageId);
                return;
            }
        });

        // ✅ FIXED: Regenerate function that handles both real-time and loaded messages
        async function regenerateMessage(modelId, messageId, btn) {
            const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
            if (!messageElement) {
                console.error('Message element not found:', messageId);
                alert('Unable to find message to regenerate');
                return;
            }
            
            console.log('🔍 Found message element:', messageElement);
            
            const conversationEntry = messageElement.closest('.conversation-entry');
            if (!conversationEntry) {
                console.error('Conversation entry not found');
                alert('Unable to find conversation entry');
                return;
            }
            
            console.log('🔍 Found conversation entry:', conversationEntry);
            
            let userPromptDiv = conversationEntry.querySelector('.user-prompt');
            
            if (!userPromptDiv) {
                console.log('⚠️ User prompt not in same entry, looking at previous entry...');
                const previousEntry = conversationEntry.previousElementSibling;
                if (previousEntry && previousEntry.classList.contains('conversation-entry')) {
                    userPromptDiv = previousEntry.querySelector('.user-prompt');
                    console.log('🔍 Found user prompt in previous entry:', userPromptDiv);
                }
            } else {
                console.log('✅ Found user prompt in same entry:', userPromptDiv);
            }
            
            if (!userPromptDiv) {
                console.error('User prompt not found');
                alert('Unable to find the original user message');
                return;
            }
            
            const clone = userPromptDiv.cloneNode(true);
            clone.querySelectorAll('.attachment-badge').forEach(el => el.remove());
            clone.querySelectorAll('.message-actions').forEach(el => el.remove());
            
            let userPrompt = clone.textContent.trim();
            
            if (!userPrompt) {
                console.warn('Could not extract text from DOM, trying conversation history...');
                const conversationDiv = document.getElementById(`conversation-${modelId}`);
                if (conversationDiv) {
                    const allEntries = Array.from(conversationDiv.querySelectorAll('.conversation-entry'));
                    const currentIndex = allEntries.indexOf(conversationEntry);
                    
                    if (currentIndex > 0 && conversationHistory[modelId] && conversationHistory[modelId][currentIndex]) {
                        userPrompt = conversationHistory[modelId][currentIndex].prompt;
                        console.log('✅ Retrieved from history:', userPrompt);
                    } else if (currentIndex === 0 && conversationHistory[modelId] && conversationHistory[modelId][0]) {
                        userPrompt = conversationHistory[modelId][0].prompt;
                        console.log('✅ Retrieved from history (first entry):', userPrompt);
                    }
                }
            }
            
            if (!userPrompt) {
                console.error('Could not extract user prompt text');
                alert('Unable to find the original user message text');
                return;
            }
            
            console.log('✅ Regenerating with prompt:', userPrompt);
            
            const assistantResponse = conversationEntry.querySelector('.assistant-response') || messageElement.closest('.assistant-response');
            if (!assistantResponse) {
                console.error('Assistant response not found');
                alert('Unable to find assistant response');
                return;
            }
            
            const messageContent = assistantResponse.querySelector('.message-content');
            if (!messageContent) {
                console.error('Message content not found');
                alert('Unable to find message content');
                return;
            }
            
            messageContent.innerHTML = `
                <div class="regenerating-indicator">
                    <div class="spinner-sm"></div>
                    <span>Regenerating response...</span>
                </div>
            `;
            
            btn.disabled = true;
            btn.style.opacity = '0.5';
            
            try {
                const formData = new FormData();
                formData.append('message', userPrompt);
                
                // ✅ Handle model selection based on optimization mode
                if (currentOptimizationMode === 'smart_all') {
                    // For Smart (All), send a special indicator to backend
                    formData.append('models', JSON.stringify(['smart_all_auto']));
                } else if (currentOptimizationMode === 'smart_same') {
                    // ✅ FIXED: For Smart (Same), extract providers and send their first models
                    const providerModels = selectedModels.map(m => {
                        const provider = m.provider;
                        const providerCheckbox = document.querySelector(`input.provider-checkbox[value="${provider}"]`);
                        return providerCheckbox ? providerCheckbox.dataset.firstModel : m.model;
                    });
                    formData.append('models', JSON.stringify(providerModels));
                } else {
                    // For Fixed, send selected models
                    formData.append('models', JSON.stringify(selectedModels.map(m => m.model)));
                }
                
                formData.append('web_search', '0');
                formData.append('create_image', '0');
                
                // ✅ FIX: Always pass optimization_mode
                formData.append('optimization_mode', currentOptimizationMode);
                
                if (currentHexCode) {
                    formData.append('hex_code', currentHexCode);
                }

                const response = await fetch('{{ route("chat.multi-compare") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                let fullResponse = '';
                let actualModelIdForResponse = modelId; // Track the actual model ID to update
                
                messageContent.innerHTML = '';
                
                function readStream() {
                    return reader.read().then(({ done, value }) => {
                        if (done) {
                            const conversationDiv = document.getElementById(`conversation-${modelId}`);
                            if (conversationDiv) {
                                const allEntries = Array.from(conversationDiv.querySelectorAll('.conversation-entry'));
                                const entryIndex = allEntries.indexOf(conversationEntry);
                                
                                if (entryIndex !== -1 && conversationHistory[modelId] && conversationHistory[modelId][entryIndex]) {
                                    conversationHistory[modelId][entryIndex].response = fullResponse;
                                    console.log('✅ Updated conversation history at index:', entryIndex);
                                }
                            }
                            
                            processMessageContent(messageContent);
                            btn.disabled = false;
                            btn.style.opacity = '1';
                            
                            console.log('✅ Regeneration complete');
                            return;
                        }
                        
                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n');
                        buffer = lines.pop();
                        
                        lines.forEach(line => {
                            if (line.trim() && line.startsWith('data: ')) {
                                const data = line.slice(6);
                                if (data === '[DONE]') {
                                    return;
                                }
                                
                                try {
                                    const parsed = JSON.parse(data);
                                    
                                    // ✅ FIX: Handle init message to set up model mapping
                                    if (parsed.type === 'init' && parsed.optimized_models) {
                                        console.log('Regenerate: Models optimized:', parsed.optimized_models);
                                        
                                        // Find which optimized model maps to our panel
                                        Object.keys(parsed.optimized_models).forEach(original => {
                                            const optimized = parsed.optimized_models[original];
                                            
                                            if (currentOptimizationMode === 'smart_all') {
                                                modelIdMapping[optimized] = 'smart_all_auto';
                                            } else if (currentOptimizationMode === 'smart_same') {
                                                // ✅ FIX: For smart_same, find the provider of the optimized model
                                                // Look through all model checkboxes (even unchecked ones) to find the provider
                                                let providerFound = null;
                                                document.querySelectorAll('input.model-checkbox').forEach(cb => {
                                                    if (cb.value === optimized) {
                                                        providerFound = cb.dataset.provider;
                                                    }
                                                });
                                                
                                                if (providerFound) {
                                                    modelIdMapping[optimized] = `${providerFound}_smart_panel`;
                                                    console.log(`Mapped ${optimized} to ${providerFound}_smart_panel`);
                                                } else {
                                                    // Fallback: extract provider from the current modelId
                                                    const provider = modelId.replace('_smart_panel', '');
                                                    modelIdMapping[optimized] = `${provider}_smart_panel`;
                                                    console.log(`Fallback mapped ${optimized} to ${provider}_smart_panel`);
                                                }
                                            } else {
                                                modelIdMapping[optimized] = original;
                                            }
                                        });
                                        
                                        console.log('Regenerate: Model ID mapping:', modelIdMapping);
                                    }
                                    
                                    // ✅ FIX: Use modelIdMapping to find the correct panel
                                    const mappedModelId = modelIdMapping[parsed.model] || parsed.model;
                                    
                                    if (parsed.type === 'chunk' && mappedModelId === modelId) {
                                        fullResponse = parsed.full_response || fullResponse;
                                        messageContent.textContent = fullResponse;
                                    } else if (parsed.type === 'complete' && mappedModelId === modelId) {
                                        fullResponse = parsed.final_response || parsed.full_response;
                                        messageContent.textContent = fullResponse;
                                    } else if (parsed.type === 'error' && mappedModelId === modelId) {
                                        messageContent.innerHTML = `<span class="text-red-600">Error: ${parsed.error}</span>`;
                                    }
                                } catch (e) {
                                    console.error('Error parsing JSON:', e);
                                }
                            }
                        });
                        
                        return readStream();
                    });
                }
                
                await readStream();
                
            } catch (error) {
                console.error('Regenerate error:', error);
                messageContent.innerHTML = `<span class="text-red-600">Failed to regenerate: ${error.message}</span>`;
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        }


        // ✅ UPDATED: Show translate dropdown with better language names
        function showTranslateDropdown(btn, messageId) {
            // Check if dropdown already exists for this button
            const existingDropdown = btn.parentElement.querySelector('.translate-dropdown');
            if (existingDropdown) {
                existingDropdown.remove();
                return; // Toggle off - just close it
            }

            // Remove any other existing dropdowns
            document.querySelectorAll('.translate-dropdown').forEach(d => d.remove());

            const languages = [
                { code: 'Spanish', name: 'Spanish' },
                { code: 'French', name: 'French' },
                { code: 'German', name: 'German' },
                { code: 'Italian', name: 'Italian' },
                { code: 'Portuguese', name: 'Portuguese' },
                { code: 'Russian', name: 'Russian' },
                { code: 'Japanese', name: 'Japanese' },
                { code: 'Korean', name: 'Korean' },
                { code: 'Chinese', name: 'Chinese' },
                { code: 'Arabic', name: 'Arabic' },
                { code: 'Hindi', name: 'Hindi' },
                { code: 'Bengali', name: 'Bengali' },
                { code: 'Dutch', name: 'Dutch' },
                { code: 'Turkish', name: 'Turkish' },
                { code: 'Vietnamese', name: 'Vietnamese' }
            ];
            
            const dropdown = document.createElement('div');
            dropdown.className = 'translate-dropdown show';
            dropdown.innerHTML = languages.map(lang => `
                <div class="translate-option" data-lang="${lang.code}" data-message-id="${messageId}">
                    ${lang.name}
                </div>
            `).join('');
            
            btn.parentElement.style.position = 'relative';
            btn.parentElement.appendChild(dropdown);
            
            // Position dropdown
            dropdown.style.position = 'absolute';
            dropdown.style.top = '100%';
            dropdown.style.left = '0';
            
            // Close dropdown when clicking outside
            setTimeout(() => {
                document.addEventListener('click', function closeDropdown(e) {
                    if (!dropdown.contains(e.target) && e.target !== btn) {
                        dropdown.remove();
                        document.removeEventListener('click', closeDropdown);
                    }
                });
            }, 10);
            
            // Handle language selection
            dropdown.querySelectorAll('.translate-option').forEach(option => {
                option.addEventListener('click', async () => {
                    const targetLang = option.dataset.lang;
                    const messageId = option.dataset.messageId;
                    dropdown.remove();
                    await translateMessage(messageId, targetLang, btn);
                });
            });
        }

        // ✅ FIXED: Translate message function - shows INLINE below the message
        async function translateMessage(messageId, targetLang, btn) {
            const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
            if (!messageElement) {
                alert('Message not found');
                return;
            }
            
            const messageContent = messageElement.querySelector('.message-content') || messageElement;
            const originalText = messageContent.textContent.trim();
            
            if (!originalText) {
                alert('No text to translate');
                return;
            }
            
            // Check if translation already exists and remove it
            const existingTranslation = messageElement.querySelector('.translation-inline');
            if (existingTranslation) {
                existingTranslation.remove();
            }
            
            btn.innerHTML = '<i class="las la-spinner la-spin"></i> Translating...';
            btn.disabled = true;
            
            try {
                console.log('Translating to:', targetLang);
                
                const response = await fetch('{{ route("translate.text") }}', {
                    method: 'POST',
                    body: JSON.stringify({
                        text: originalText,
                        target_lang: targetLang
                    }),
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                console.log('Translation response status:', response.status);
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: 'Unknown error' }));
                    throw new Error(errorData.error || `HTTP ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Translation successful');
                
                if (!data.translatedText) {
                    throw new Error('No translation returned');
                }
                
                // ✅ Show translation INLINE below the message
                showTranslationInline(messageElement, originalText, data.translatedText, targetLang);
                
            } catch (error) {
                console.error('Translation error:', error);
                alert('Translation failed: ' + error.message);
            } finally {
                btn.innerHTML = '<i class="las la-language"></i> Translate';
                btn.disabled = false;
            }
        }

        // ✅ NEW: Show translation inline below the message
        function showTranslationInline(messageElement, originalText, translatedText, targetLang) {
            // Remove any existing translation
            const existingTranslation = messageElement.querySelector('.translation-inline');
            if (existingTranslation) {
                existingTranslation.remove();
            }
            
            // Create translation container
            const translationDiv = document.createElement('div');
            translationDiv.className = 'translation-inline';
            translationDiv.innerHTML = `
                <div class="translation-inline-header">
                    <span class="translation-inline-label">
                        <i class="las la-language"></i> Translation (${targetLang})
                    </span>
                    <div class="translation-inline-actions">
                        <button class="translation-inline-btn copy-translation-btn">
                            <i class="las la-copy"></i> Copy
                        </button>
                        <button class="translation-inline-btn close-translation-btn">
                            <i class="las la-times"></i> Close
                        </button>
                    </div>
                </div>
                <div class="translation-inline-text">${escapeHtml(translatedText)}</div>
            `;
            
            // Find where to insert - after message content but inside the message element
            const messageContent = messageElement.querySelector('.message-content');
            if (messageContent) {
                messageContent.parentElement.insertBefore(translationDiv, messageContent.nextSibling);
            } else {
                messageElement.appendChild(translationDiv);
            }
            
            // Add event listeners for the inline actions
            const copyBtn = translationDiv.querySelector('.copy-translation-btn');
            copyBtn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(translatedText);
                    const originalHTML = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="las la-check"></i> Copied!';
                    setTimeout(() => {
                        copyBtn.innerHTML = originalHTML;
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy:', err);
                }
            });
            
            const closeBtn = translationDiv.querySelector('.close-translation-btn');
            closeBtn.addEventListener('click', () => {
                translationDiv.remove();
            });
            
            // Scroll to show the translation
            translationDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // ✅ NEW: Show translation modal
        function showTranslationModal(originalText, translatedText, targetLang) {
            // Create modal
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-4">
                        <h3 class="text-lg font-semibold">Translation</h3>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[60vh]">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Original:</label>
                            <div class="bg-gray-50 p-4 rounded-lg text-sm text-gray-800 whitespace-pre-wrap">${escapeHtml(originalText)}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Translated (${targetLang.toUpperCase()}):</label>
                            <div class="bg-purple-50 p-4 rounded-lg text-sm text-gray-800 whitespace-pre-wrap">${escapeHtml(translatedText)}</div>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 px-6 py-4 bg-gray-50 border-t">
                        <button class="copy-translation-btn px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            <i class="las la-copy"></i> Copy Translation
                        </button>
                        <button class="close-translation-modal px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Close modal handlers
            modal.querySelector('.close-translation-modal').addEventListener('click', () => {
                modal.remove();
            });
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
            
            // Copy translation
            modal.querySelector('.copy-translation-btn').addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(translatedText);
                    const btn = modal.querySelector('.copy-translation-btn');
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="las la-check"></i> Copied!';
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy:', err);
                }
            });
        }

        function getEmptyStateHTML() {
            return `
                <div class="text-center text-gray-500 mt-8">
                    <i class="las la-comments text-4xl mb-2"></i>
                    <p>Start a conversation to see responses here</p>
                </div>
            `;
        }

        // ✅ File input change handler with better preview
        fileInput.addEventListener('change', async function(e) {
            const newFiles = Array.from(e.target.files);
            if (newFiles.length === 0) return;
            
            // ✅ NEW: Validate total file count
            if (attachedFiles.length + newFiles.length > MAX_FILES) {
                showNotification(`Maximum ${MAX_FILES} files allowed`, 'error');
                e.target.value = '';
                return;
            }
            
            // ✅ NEW: Validate file sizes and types
            const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB per file
            const MAX_TOTAL_SIZE = 50 * 1024 * 1024; // 50MB total
            const allowedTypes = [
                'image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            
            let currentTotalSize = attachedFiles.reduce((sum, file) => sum + file.size, 0);
            const validFiles = [];
            const errors = [];
            
            for (const file of newFiles) {
                // Check individual file size
                if (file.size > MAX_FILE_SIZE) {
                    errors.push(`"${file.name}" exceeds 10MB limit (${(file.size / 1024 / 1024).toFixed(2)}MB)`);
                    continue;
                }
                
                // Check total size
                if (currentTotalSize + file.size > MAX_TOTAL_SIZE) {
                    errors.push(`Adding "${file.name}" would exceed 50MB total limit`);
                    continue;
                }
                
                // Check file type
                if (!allowedTypes.includes(file.type)) {
                    errors.push(`"${file.name}" type not supported. Allowed: PDF, DOC, DOCX, PNG, JPG, JPEG, WEBP, GIF`);
                    continue;
                }
                
                validFiles.push(file);
                currentTotalSize += file.size;
            }
            
            // Show errors if any
            if (errors.length > 0) {
                errors.forEach(error => showNotification(error, 'error'));
            }
            
            // Add valid files
            if (validFiles.length > 0) {
                attachedFiles = [...attachedFiles, ...validFiles];
                updateFilePreview();
                attachmentPreview.classList.remove('hidden');
                
                // ✅ NEW: Show success message
                if (validFiles.length === 1) {
                    showNotification(`File "${validFiles[0].name}" added successfully`, 'success');
                } else {
                    showNotification(`${validFiles.length} files added successfully`, 'success');
                }
            }
            
            e.target.value = '';
        });

        // ✅ Function to update file preview display
        function updateFilePreview() {
            fileNameSpan.innerHTML = '';
            
            if (attachedFiles.length === 0) {
                attachmentPreview.classList.add('hidden');
                return;
            }
            
            if (attachedFiles.length === 1) {
                // Single file - show detailed preview
                displaySingleFilePreview(attachedFiles[0]);
            } else {
                // Multiple files - show compact grid
                displayMultipleFilesPreview();
            }
        }

        // ✅ Display single file preview
        function displaySingleFilePreview(file) {
            const fileType = file.type.toLowerCase();
            const fileName = file.name;
            const fileSize = (file.size / 1024).toFixed(2) + ' KB';
            
            if (fileType.startsWith('image/')) {
                // For images, show thumbnail
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'max-h-16 max-w-32 rounded border-2 border-white/30 cursor-pointer hover:opacity-80 transition-opacity';
                    img.onclick = () => openAttachmentPreview(file);
                    fileNameSpan.innerHTML = '';
                    fileNameSpan.appendChild(img);
                };
                reader.readAsDataURL(file);
            } else {
                // For non-image files, show file info
                const fileInfo = document.createElement('div');
                fileInfo.className = 'flex items-center space-x-2';
                
                let icon = 'la-file';
                let iconColor = 'text-white';
                if (fileType.includes('pdf')) {
                    icon = 'la-file-pdf';
                    iconColor = 'text-red-300';
                } else if (fileType.includes('word') || fileType.includes('document')) {
                    icon = 'la-file-word';
                    iconColor = 'text-blue-300';
                }
                
                const displayName = fileName.length > 25 ? fileName.substring(0, 22) + '...' : fileName;
                
                fileInfo.innerHTML = `
                    <i class="las ${icon} ${iconColor} text-2xl"></i>
                    <div>
                        <div class="font-medium text-sm" title="${fileName}">${displayName}</div>
                        <div class="text-xs opacity-75">${fileSize}</div>
                    </div>
                `;
                fileNameSpan.innerHTML = '';
                fileNameSpan.appendChild(fileInfo);
            }
        }

        // ✅ Display multiple files preview (compact grid)
        function displayMultipleFilesPreview() {
            const container = document.createElement('div');
            container.className = 'flex flex-wrap gap-2 max-w-full';
            
            attachedFiles.forEach((file, index) => {
                const fileType = file.type.toLowerCase();
                const fileName = file.name;
                const fileSize = (file.size / 1024).toFixed(2) + ' KB';
                
                const fileCard = document.createElement('div');
                fileCard.className = 'flex items-center space-x-2 bg-white/10 rounded-lg p-2 pr-1 max-w-[200px] group hover:bg-white/20 transition-colors';
                
                if (fileType.startsWith('image/')) {
                    // Show thumbnail for images
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-8 h-8 object-cover rounded';
                        fileCard.insertBefore(img, fileCard.firstChild);
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Show icon for non-images
                    let icon = 'la-file';
                    let iconColor = 'text-white';
                    if (fileType.includes('pdf')) {
                        icon = 'la-file-pdf';
                        iconColor = 'text-red-300';
                    } else if (fileType.includes('word') || fileType.includes('document')) {
                        icon = 'la-file-word';
                        iconColor = 'text-blue-300';
                    }
                    
                    const iconDiv = document.createElement('i');
                    iconDiv.className = `las ${icon} ${iconColor} text-xl flex-shrink-0`;
                    fileCard.insertBefore(iconDiv, fileCard.firstChild);
                }
                
                // File info
                const displayName = fileName.length > 15 ? fileName.substring(0, 12) + '...' : fileName;
                const fileInfo = document.createElement('div');
                fileInfo.className = 'flex-1 min-w-0';
                fileInfo.innerHTML = `
                    <div class="text-xs font-medium truncate" title="${fileName}">${displayName}</div>
                    <div class="text-[10px] opacity-75">${fileSize}</div>
                `;
                fileCard.appendChild(fileInfo);
                
                // Remove button (shows on hover)
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'text-white/50 hover:text-red-400 transition-colors opacity-0 group-hover:opacity-100 p-1 flex-shrink-0';
                removeBtn.innerHTML = '<i class="las la-times text-sm"></i>';
                removeBtn.title = 'Remove file';
                removeBtn.onclick = function(e) {
                    e.stopPropagation();
                    removeFile(index);
                };
                fileCard.appendChild(removeBtn);
                
                // Click to preview
                fileCard.style.cursor = 'pointer';
                fileCard.onclick = function(e) {
                    if (e.target !== removeBtn && !removeBtn.contains(e.target)) {
                        openAttachmentPreview(file);
                    }
                };
                
                container.appendChild(fileCard);
            });
            
            // Add file count badge
            if (attachedFiles.length > 1) {
                const badge = document.createElement('div');
                badge.className = 'flex items-center justify-center bg-purple-600 text-white text-xs font-medium px-3 py-1 rounded-full';
                badge.innerHTML = `<i class="las la-paperclip mr-1"></i>${attachedFiles.length} files`;
                container.appendChild(badge);
            }
            
            fileNameSpan.innerHTML = '';
            fileNameSpan.appendChild(container);
        }

        // ✅ Remove single file from array
        function removeFile(index) {
            const removedFile = attachedFiles[index];
            attachedFiles.splice(index, 1);
            updateFilePreview();
            
            if (attachedFiles.length === 0) {
                attachmentPreview.classList.add('hidden');
            }
            
            console.log(`Removed file: ${removedFile.name}. Remaining: ${attachedFiles.length}`);
        }

        // Add the helper functions from FRONTEND_MULTIPLE_FILES.js here
        removeFileButton.addEventListener('click', function() {
            fileInput.value = '';
            attachedFiles = [];
            currentAttachmentFile = null;
            updateFilePreview();
            attachmentPreview.classList.add('hidden');
            console.log('All files removed');
        });

        // ✅ NEW: Preview attachment button handler
        previewAttachmentBtn.addEventListener('click', async function() {
            if (currentAttachmentFile) {
                await openAttachmentPreview(currentAttachmentFile);
            }
        });

        // ✅ NEW: Close preview modal handlers
        closePreviewModalBtn.addEventListener('click', () => {
            previewModal.classList.add('hidden');
        });

        closePreviewModalFooterBtn.addEventListener('click', () => {
            previewModal.classList.add('hidden');
        });

        previewModal.addEventListener('click', (e) => {
            if (e.target.id === 'attachment-preview-modal') {
                previewModal.classList.add('hidden');
            }
        });

        // ✅ NEW: Download attachment handler
        downloadAttachmentBtn.addEventListener('click', () => {
            if (currentAttachmentFile) {
                const url = URL.createObjectURL(currentAttachmentFile);
                const a = document.createElement('a');
                a.href = url;
                a.download = currentAttachmentFile.name;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
        });

        // ✅ NEW: Function to open attachment preview
        async function openAttachmentPreview(file, downloadUrl = null) {
            previewModal.classList.remove('hidden');
            previewModalTitle.textContent = 'File Preview';
            previewModalFilename.textContent = file.name || 'Unknown file';
            
            // Show loading state
            previewContent.innerHTML = `
                <div class="preview-loading">
                    <div class="spinner"></div>
                    <p class="mt-4">Loading preview...</p>
                </div>
            `;

            const fileType = (file.type || '').toLowerCase();
            
            try {
                if (fileType.startsWith('image/')) {
                    await previewImage(file);
                } else if (fileType.includes('pdf') || file.name.toLowerCase().endsWith('.pdf')) {
                    await previewPDF(file);
                } else if (fileType.includes('word') || fileType.includes('document') || 
                           file.name.toLowerCase().endsWith('.docx') || file.name.toLowerCase().endsWith('.doc')) {
                    await previewDOCX(file);
                } else {
                    showUnsupportedPreview(file);
                }
            } catch (error) {
                console.error('Preview error:', error);
                previewContent.innerHTML = `
                    <div class="text-center text-red-600 py-8">
                        <i class="las la-exclamation-circle text-4xl mb-2"></i>
                        <p>Failed to load preview</p>
                        <p class="text-sm text-gray-600 mt-2">${error.message}</p>
                    </div>
                `;
            }
            
            // Update download button
            if (downloadUrl) {
                downloadAttachmentBtn.onclick = () => {
                    const a = document.createElement('a');
                    a.href = downloadUrl;
                    a.download = file.name;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                };
            }
        }

        // ✅ NEW: Preview image
        async function previewImage(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewContent.innerHTML = `
                        <div class="flex items-center justify-center">
                            <img src="${e.target.result}" 
                                 alt="${file.name}" 
                                 class="max-w-full max-h-[70vh] rounded-lg shadow-lg">
                        </div>
                    `;
                    resolve();
                };
                reader.onerror = () => reject(new Error('Failed to read image file'));
                reader.readAsDataURL(file);
            });
        }

        // ✅ NEW: Preview PDF
        async function previewPDF(file) {
            if (typeof pdfjsLib === 'undefined') {
                throw new Error('PDF.js library not loaded');
            }

            return new Promise(async (resolve, reject) => {
                try {
                    const arrayBuffer = await file.arrayBuffer();
                    const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                    
                    previewContent.innerHTML = `
                        <div class="space-y-4">
                            <div class="text-center text-gray-600 mb-4">
                                <p class="font-semibold">Total Pages: ${pdf.numPages}</p>
                            </div>
                            <div id="pdf-pages-container"></div>
                        </div>
                    `;
                    
                    const pagesContainer = document.getElementById('pdf-pages-container');
                    
                    // Render first 10 pages (to avoid performance issues)
                    const maxPages = Math.min(pdf.numPages, 10);
                    
                    for (let pageNum = 1; pageNum <= maxPages; pageNum++) {
                        const page = await pdf.getPage(pageNum);
                        const viewport = page.getViewport({ scale: 1.5 });
                        
                        const canvas = document.createElement('canvas');
                        canvas.className = 'pdf-page-canvas w-full';
                        canvas.width = viewport.width;
                        canvas.height = viewport.height;
                        
                        const pageDiv = document.createElement('div');
                        pageDiv.className = 'mb-4';
                        pageDiv.innerHTML = `
                            <div class="text-sm text-gray-600 mb-2 font-semibold">Page ${pageNum}</div>
                        `;
                        pageDiv.appendChild(canvas);
                        pagesContainer.appendChild(pageDiv);
                        
                        const context = canvas.getContext('2d');
                        await page.render({
                            canvasContext: context,
                            viewport: viewport
                        }).promise;
                    }
                    
                    if (pdf.numPages > 10) {
                        pagesContainer.innerHTML += `
                            <div class="text-center text-gray-600 py-4">
                                <p>Showing first 10 pages of ${pdf.numPages}</p>
                                <p class="text-sm">Download the file to view all pages</p>
                            </div>
                        `;
                    }
                    
                    resolve();
                } catch (error) {
                    reject(error);
                }
            });
        }

        // ✅ NEW: Preview DOCX
        async function previewDOCX(file) {
            if (typeof mammoth === 'undefined') {
                throw new Error('Mammoth.js library not loaded');
            }

            return new Promise(async (resolve, reject) => {
                try {
                    const arrayBuffer = await file.arrayBuffer();
                    const result = await mammoth.convertToHtml({ arrayBuffer: arrayBuffer });
                    
                    previewContent.innerHTML = `
                        <div class="docx-preview">
                            ${result.value}
                        </div>
                    `;
                    
                    if (result.messages.length > 0) {
                        console.warn('DOCX conversion messages:', result.messages);
                    }
                    
                    resolve();
                } catch (error) {
                    reject(error);
                }
            });
        }

        // ✅ NEW: Show unsupported file type
        function showUnsupportedPreview(file) {
            const fileSize = (file.size / 1024).toFixed(2);
            const fileType = file.type || 'Unknown';
            
            previewContent.innerHTML = `
                <div class="text-center text-gray-600 py-8">
                    <i class="las la-file text-6xl mb-4"></i>
                    <p class="text-lg font-semibold mb-2">Preview not available</p>
                    <p class="text-sm">This file type cannot be previewed</p>
                    <div class="mt-6 inline-block bg-gray-100 rounded-lg p-4 text-left">
                        <div class="text-sm space-y-1">
                            <p><strong>File Name:</strong> ${file.name}</p>
                            <p><strong>File Type:</strong> ${fileType}</p>
                            <p><strong>File Size:</strong> ${fileSize} KB</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mt-4">Click download to save this file</p>
                </div>
            `;
        }

        compareForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (selectedModels.length === 0) {
                alert('Please select at least one model to compare.');
                return;
            }

            const message = messageInput.value.trim();
            if (!message) {
                alert('Please enter a message.');
                return;
            }

            // ✅ NEW: Auto-detect image generation intent
            const createImageCheckbox = document.getElementById('create-image');
            if (!createImageCheckbox.checked && detectImageGenerationIntent(message)) {
                const supportsImageGen = selectedModels.some(m => modelSupportsImageGen(m.model));
                if (supportsImageGen) {
                    createImageCheckbox.checked = true;
                    console.log('🎨 Auto-enabled image generation based on message content');
                }
            }

            startComparison();
        });

        // ✅ MOBILE: Handle Enter key differently on mobile vs desktop
        function isMobileDevice() {
            return /Android|iPhone|iPad|iPod|Opera Mini|IEMobile|WPDesktop/i.test(navigator.userAgent) || 
                   (window.innerWidth <= 768 && 'ontouchstart' in window);
        }

        messageInput.addEventListener('keydown', (e) => {
            const isMobile = isMobileDevice();

            if (e.key === 'Enter') {
                if (isMobile) {
                    // On mobile, allow new line (default behavior)
                    return;
                }

                // On desktop, submit on Enter (unless Shift is held)
                if (!e.shiftKey) {
                    e.preventDefault();
                    compareForm.dispatchEvent(new Event('submit'));
                }
            }
        });

        // ✅ NEW: Intelligent image generation detection
        function detectImageGenerationIntent(message) {
            const imageTriggerKeywords = [
                'generate image',
                'generate an image',
                'create image',
                'create an image',
                'make image',
                'make an image',
                'draw image',
                'draw an image',
                'draw a picture',
                'create picture',
                'generate picture',
                'paint image',
                'paint picture',
                'design image',
                'design picture',
                'illustrate',
                'sketch',
                'render image',
                'produce image',
                'show me image',
                'show me picture',
                'visualize'
            ];
            
            const messageLower = message.toLowerCase();
            
            // Check if message contains any trigger keywords
            return imageTriggerKeywords.some(keyword => messageLower.startsWith(keyword));
        }

        // ✅ UPDATE: Modified startComparison() to include optimization_mode
        function startComparison() {
            const message = messageInput.value.trim();
            const formData = new FormData();
            
            // ✅ ADD: HTML encode the message
            const encodedMessage = btoa(unescape(encodeURIComponent(message)));

            // Update FormData
            formData.append('message', encodedMessage);
            formData.append('is_encoded', '1'); // Flag to decode on backend
            
            // ✅ Handle model selection based on optimization mode
            if (currentOptimizationMode === 'smart_all') {
                // For Smart (All), send a special indicator to backend
                formData.append('models', JSON.stringify(['smart_all_auto']));
            } else if (currentOptimizationMode === 'smart_same') {
                // ✅ FIXED: For Smart (Same), send the first model from each selected provider
                const providerModels = selectedModels.map(m => {
                    const provider = m.provider;
                    const providerCheckbox = document.querySelector(`input.provider-checkbox[value="${provider}"]`);
                    return providerCheckbox ? providerCheckbox.dataset.firstModel : m.model;
                });
                formData.append('models', JSON.stringify(providerModels));
            } else {
                // For Fixed, send selected models as-is
                formData.append('models', JSON.stringify(selectedModels.map(m => m.model)));
            }
            
            formData.append('web_search', document.getElementById('web-search').checked ? '1' : '0');
            formData.append('create_image', document.getElementById('create-image').checked ? '1' : '0');
            
            // Add optimization mode
            formData.append('optimization_mode', currentOptimizationMode);
            
            if (currentHexCode) {
                formData.append('hex_code', currentHexCode);
            }
            
            // ✅ NEW: Show upload progress
            if (attachedFiles.length > 0) {
                const uploadProgress = document.getElementById('upload-progress');
                const fileStatusMessages = document.getElementById('file-status-messages');
                
                if (uploadProgress) {
                    uploadProgress.classList.remove('hidden');
                }
                
                if (fileStatusMessages) {
                    fileStatusMessages.innerHTML = `
                        <div class="text-xs text-indigo-600 flex items-center space-x-1">
                            <i class="las la-info-circle"></i>
                            <span>Uploading ${attachedFiles.length} file(s)...</span>
                        </div>
                    `;
                }
                
                attachedFiles.forEach((file) => {
                    formData.append('files[]', file);
                });
            }

            console.log('Starting comparison with mode:', currentOptimizationMode);

            sendButton.classList.add('hidden');
            stopButton.classList.remove('hidden');
            messageInput.disabled = true;
            optionsDropdownBtn.disabled = true;

            // ✅ For Smart (All), ensure we have the panel
            if (currentOptimizationMode === 'smart_all' && selectedModels.length === 0) {
                selectedModels = [{
                    model: 'smart_all_auto',
                    provider: 'smart_all',
                    displayName: 'Smart Mode'
                }];
                generateModelPanels();
            }

            selectedModels.forEach(model => {
                addMessageToConversation(model.model, 'user', message);
                updateModelStatus(model.model, 'waiting');
            });

            messageInput.value = '';
            messageInput.style.height = 'auto';
            fileInput.value = '';
            attachedFiles = [];  // ✅ ADDED
            currentAttachmentFile = null;
            updateFilePreview();  // ✅ ADDED
            attachmentPreview.classList.add('hidden');


            document.getElementById('create-image').checked = false;
            document.getElementById('web-search').checked = false;

            abortController = new AbortController();

            fetch('{{ route("chat.multi-compare") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                signal: abortController.signal
            })
            .then(response => {
                // ✅ NEW: Hide upload progress on response
                const uploadProgress = document.getElementById('upload-progress');
                if (uploadProgress) {
                    uploadProgress.classList.add('hidden');
                }
                
                if (!response.ok) {
                    // ✅ NEW: Better error handling with file info
                    return response.json().then(data => {
                        throw new Error(data.error || `HTTP error! status: ${response.status}`);
                    }).catch(err => {
                        if (err.message) throw err;
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                }
                return response.body;
            })
            .then(body => {
                const reader = body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                function readStream() {
                    return reader.read().then(({ done, value }) => {
                        if (done) {
                            debugLog('Stream completed');
                            resetUI();
                            return;
                        }

                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n');
                        buffer = lines.pop();

                        lines.forEach(line => {
                            if (line.trim() && line.startsWith('data: ')) {
                                const data = line.slice(6);
                                if (data === '[DONE]') {
                                    debugLog('Stream done');
                                    resetUI();
                                    return;
                                }

                                try {
                                    const parsed = JSON.parse(data);
                                    handleStreamMessage(parsed);
                                } catch (e) {
                                    console.error('Error parsing JSON:', e, data);
                                }
                            }
                        });

                        return readStream();
                    });
                }

                return readStream();
            })
            .catch(error => {
                // ✅ NEW: Hide upload progress on error
                const uploadProgress = document.getElementById('upload-progress');
                const fileStatusMessages = document.getElementById('file-status-messages');
                
                if (uploadProgress) {
                    uploadProgress.classList.add('hidden');
                }
                
                if (error.name === 'AbortError') {
                    debugLog('Request aborted');
                    if (fileStatusMessages) {
                        fileStatusMessages.innerHTML = `
                            <div class="text-xs text-yellow-600 flex items-center space-x-1">
                                <i class="las la-exclamation-triangle"></i>
                                <span>Upload cancelled</span>
                            </div>
                        `;
                    }
                } else {
                    console.error('Error:', error);
                    
                    // ✅ NEW: Show detailed error message
                    const errorMessage = error.message || 'An unknown error occurred';
                    showNotification(errorMessage, 'error');
                    
                    if (fileStatusMessages) {
                        fileStatusMessages.innerHTML = `
                            <div class="text-xs text-red-600 flex items-center space-x-1">
                                <i class="las la-times-circle"></i>
                                <span>Upload failed: ${errorMessage}</span>
                            </div>
                        `;
                    }
                }
                resetUI();
            });
        }

        // ✅ Helper function to create download card
        function createDownloadCard(conversationDiv, data) {
            const downloadCard = document.createElement('div');
            downloadCard.className = 'file-download-card bg-gradient-to-r from-green-50 to-blue-50 border-2 border-green-500 rounded-lg p-6 my-4 shadow-lg';
            downloadCard.innerHTML = `
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center shadow-lg">
                            <i class="las la-download text-white text-3xl"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center gap-2">
                            <i class="las la-check-circle text-green-600"></i>
                            Export Complete
                        </h3>
                        <div class="flex items-center space-x-4 text-sm text-gray-600 mb-3">
                            <span class="flex items-center gap-1">
                                <i class="las la-file"></i> 
                                <strong>${data.file_name}</strong>
                            </span>
                            ${data.rows ? `
                                <span class="flex items-center gap-1">
                                    <i class="las la-table"></i> 
                                    ${data.rows} rows × ${data.columns} columns
                                </span>
                            ` : ''}
                        </div>
                        <a href="${data.download_url}" 
                        download="${data.file_name}"
                        target="_blank"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <i class="las la-download text-xl mr-2"></i>
                            Download ${data.file_format} File
                        </a>
                    </div>
                </div>
            `;
            
            // Find the last message content and append download card after it
            const lastMessageContent = conversationDiv.querySelector('.conversation-entry:last-child .message-content');
            if (lastMessageContent) {
                lastMessageContent.appendChild(downloadCard);
                console.log('✅ Download card added after message content');
            } else {
                conversationDiv.appendChild(downloadCard);
                console.log('✅ Download card added to conversation div');
            }
            
            // Scroll to show download card
            conversationDiv.scrollTop = conversationDiv.scrollHeight;
        }

        // ✅ NEW: Update handleStreamMessage to show model changes
        function handleStreamMessage(data) {
            switch (data.type) {
                case 'init':
                    debugLog('Initialized with models', data.models);
                    if (data.hex_code) {
                            currentHexCode = data.hex_code;
                            // ✅ Save hex_code to localStorage so conversation persists on refresh
                            localStorage.setItem('multi_compare_current_hex_code', data.hex_code);
                            // ✅ Update URL without page reload
                            const newUrl = `{{ url('/chattermate-compare') }}/${data.hex_code}`;
                            window.history.pushState({ hexCode: data.hex_code }, '', newUrl);
                        }
                    
                    // ✅ Build model ID mapping for optimized models (text chat)
                    if (data.optimized_models) {
                        console.log('Models optimized:', data.optimized_models);
                        modelIdMapping = {}; // Reset mapping
                        
                        Object.keys(data.optimized_models).forEach(original => {
                            const optimized = data.optimized_models[original];
                            
                            if (currentOptimizationMode === 'smart_all') {
                                modelIdMapping[optimized] = 'smart_all_auto';
                            } else if (currentOptimizationMode === 'smart_same') {
                                let providerFound = null;
                                
                                document.querySelectorAll('input.model-checkbox').forEach(cb => {
                                    if (cb.value === original) {
                                        providerFound = cb.dataset.provider;
                                    }
                                });
                                
                                if (providerFound) {
                                    modelIdMapping[optimized] = `${providerFound}_smart_panel`;
                                    console.log(`✅ Mapped ${optimized} → ${providerFound}_smart_panel`);
                                } else {
                                    if (optimized.includes('gemini') || optimized.includes('google')) {
                                        modelIdMapping[optimized] = 'gemini_smart_panel';
                                    } else if (optimized.includes('gpt') || optimized.includes('o3')) {
                                        modelIdMapping[optimized] = 'openai_smart_panel';
                                    } else if (optimized.includes('claude')) {
                                        modelIdMapping[optimized] = 'claude_smart_panel';
                                    } else if (optimized.includes('grok')) {
                                        modelIdMapping[optimized] = 'grok_smart_panel';
                                    } else {
                                        modelIdMapping[optimized] = original;
                                    }
                                    console.log(`⚠️ Fallback mapping: ${optimized} → ${modelIdMapping[optimized]}`);
                                }
                            } else {
                                modelIdMapping[optimized] = original;
                            }
                        });
                        
                        console.log('✅ Model ID mapping created:', modelIdMapping);
                    }
                    
                    // ✅ NEW: Handle image generation model mapping
                    if (data.model_mapping) {
                        imageGenModelMapping = data.model_mapping;
                        console.log('🖼️ Image generation model mapping:', imageGenModelMapping);
                    }
                    break;

                case 'model_start':
                    // ✅ Use mapped model ID if available
                    const startModelId = modelIdMapping[data.model] || imageGenModelMapping[data.actual_model] || data.model;
                    console.log(`🟢 Model starting: ${data.model} → panel: ${startModelId}`, {
                        hasTextMapping: !!modelIdMapping[data.model],
                        hasImageMapping: !!imageGenModelMapping[data.actual_model],
                        actualModel: data.actual_model
                    });
                    
                    updateModelStatus(startModelId, 'running');
                    
                    // ✅ CRITICAL: This creates the response element!
                    addMessageToConversation(startModelId, 'assistant', '', true);
                    
                    // Show optimization indicator (skip for Smart modes)
                    if (data.was_optimized && currentOptimizationMode === 'fixed') {
                        const modelPanel = document.querySelector(`[data-model-id="${startModelId}"]`);
                        if (modelPanel) {
                            const header = modelPanel.querySelector('.model-panel-header span');
                            if (header && !header.querySelector('.model-optimization-indicator')) {
                                const indicator = document.createElement('span');
                                indicator.className = 'model-optimization-indicator';
                                indicator.innerHTML = '<i class="las la-magic"></i> Optimized';
                                indicator.title = `Changed from ${data.original_model || 'previous model'}`;
                                header.appendChild(indicator);
                            }
                        }
                    }
                    break;

                case 'chunk':
                    // ✅ Use mapped model ID if available
                    const chunkModelId = modelIdMapping[data.model] || imageGenModelMapping[data.actual_model] || data.model;
                    console.log(`📝 Chunk received: ${data.model} → panel: ${chunkModelId}`);
                    updateModelResponse(chunkModelId, data.content, data.full_response);
                    break;

                case 'complete':
                    // ✅ Use mapped model ID if available - prioritize image mapping for image responses
                    let completeModelId;
                    if (data.image && data.actual_model && imageGenModelMapping[data.actual_model]) {
                        // For image responses, use the image generation mapping
                        completeModelId = imageGenModelMapping[data.actual_model];
                        console.log(`✅ Complete (IMAGE): ${data.actual_model} → panel: ${completeModelId}`, {
                            imageUrl: data.image ? data.image.substring(0, 50) + '...' : 'null',
                            usingImageMapping: true
                        });
                    } else {
                        // For text responses, use the text model mapping
                        completeModelId = modelIdMapping[data.model] || data.model;
                        console.log(`✅ Complete (TEXT): ${data.model} → panel: ${completeModelId}`);
                    }
                    
                    updateModelStatus(completeModelId, 'completed');
                    
                    if (data.image) {
                        console.log('🖼️ Image data received', {
                            model: data.model,
                            actualModel: data.actual_model,
                            mappedModel: completeModelId,
                            imageUrl: data.image ? data.image.substring(0, 50) + '...' : 'null',
                            prompt: data.prompt,
                            imageMapping: imageGenModelMapping
                        });
                        
                        finalizeModelImageResponse(completeModelId, data.image, data.prompt);
                    } else {
                        finalizeModelResponse(completeModelId, data.final_response || data.full_response);
                    }
                    break;

                case 'error':
                    // ✅ Use mapped model ID if available
                    const errorModelId = modelIdMapping[data.model] || imageGenModelMapping[data.actual_model] || data.model;
                    console.log(`❌ Error: ${data.model} → panel: ${errorModelId}`, {
                        error: data.error,
                        actualModel: data.actual_model
                    });
                    
                    updateModelStatus(errorModelId, 'error');
                    
                    // ✅ Display error message properly
                    const errorResponseElement = modelResponseElements[errorModelId];
                    if (errorResponseElement) {
                        errorResponseElement.innerHTML = `
                            <div class="text-red-600 p-4 border border-red-300 rounded-lg bg-red-50">
                                <div class="flex items-start gap-3">
                                    <i class="las la-exclamation-triangle text-2xl flex-shrink-0"></i>
                                    <div>
                                        <p class="font-semibold mb-1">Error</p>
                                        <p class="text-sm">${escapeHtml(data.error)}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        // Try to find and create response element if missing
                        console.warn(`No response element for ${errorModelId}, attempting to create...`);
                        addMessageToConversation(errorModelId, 'assistant', '', false);
                        
                        setTimeout(() => {
                            const newErrorElement = modelResponseElements[errorModelId];
                            if (newErrorElement) {
                                newErrorElement.innerHTML = `
                                    <div class="text-red-600 p-4 border border-red-300 rounded-lg bg-red-50">
                                        <div class="flex items-start gap-3">
                                            <i class="las la-exclamation-triangle text-2xl flex-shrink-0"></i>
                                            <div>
                                                <p class="font-semibold mb-1">Error</p>
                                                <p class="text-sm">${escapeHtml(data.error)}</p>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            } else {
                                console.error('Failed to create error display element');
                            }
                        }, 100);
                    }
                    
                    // Also update conversation history with error
                    if (conversationHistory[errorModelId] && conversationHistory[errorModelId].length > 0) {
                        const lastEntry = conversationHistory[errorModelId][conversationHistory[errorModelId].length - 1];
                        lastEntry.response = data.error;
                    }
                    break;

                case 'file_generated':
                    console.log('📥 File generated event received:', {
                        model: data.model,
                        file_name: data.file_name,
                        download_url: data.download_url
                    });
                    
                    // ✅ UPDATED: Better handling of model-specific file downloads
                    const targetModelId = data.model || 'export';
                    
                    // ✅ Try multiple strategies to find the right panel
                    let targetConversationDiv = null;
                    
                    // Strategy 1: Direct match with panel data-model-id
                    targetConversationDiv = document.querySelector(`[data-model-id="${targetModelId}"] .model-conversation`);
                    console.log('Strategy 1 - Direct match:', targetConversationDiv ? 'Found' : 'Not found');
                    
                    // Strategy 2: Try to find by provider for smart_same mode
                    if (!targetConversationDiv && targetModelId.includes('_smart_panel')) {
                        const provider = targetModelId.replace('_smart_panel', '');
                        targetConversationDiv = document.querySelector(`[data-model-id="${provider}_smart_panel"] .model-conversation`);
                        console.log('Strategy 2 - Provider match:', targetConversationDiv ? 'Found' : 'Not found');
                    }
                    
                    // Strategy 3: Try smart_all_auto panel
                    if (!targetConversationDiv && currentOptimizationMode === 'smart_all') {
                        targetConversationDiv = document.querySelector(`[data-model-id="smart_all_auto"] .model-conversation`);
                        console.log('Strategy 3 - Smart all:', targetConversationDiv ? 'Found' : 'Not found');
                    }
                    
                    // Strategy 4: Find the panel by looking at model checkboxes/provider checkboxes
                    if (!targetConversationDiv) {
                        // Get the provider from the model
                        const modelCheckbox = document.querySelector(`input.model-checkbox[value="${targetModelId}"]`);
                        if (modelCheckbox) {
                            const provider = modelCheckbox.dataset.provider;
                            targetConversationDiv = document.querySelector(`[data-model-id="${targetModelId}"] .model-conversation`) ||
                                                document.querySelector(`[data-model-id="${provider}_smart_panel"] .model-conversation`);
                            console.log('Strategy 4 - Via checkbox:', targetConversationDiv ? 'Found' : 'Not found');
                        }
                    }
                    
                    // Strategy 5: If still not found, add to ALL active panels (fallback)
                    if (!targetConversationDiv) {
                        console.warn('⚠️ Could not find specific panel, adding to all panels');
                        const allPanels = document.querySelectorAll('.model-conversation');
                        console.log('Found panels:', allPanels.length);
                        
                        allPanels.forEach(convDiv => {
                            createDownloadCard(convDiv, data);
                        });
                        
                        showNotification(`✅ ${data.file_name} ready for download`, 'success');
                        break;
                    }
                    
                    // ✅ Found the panel, add download card
                    console.log('✅ Adding download card to specific panel');
                    createDownloadCard(targetConversationDiv, data);
                    showNotification(`✅ ${data.file_name} ready for download`, 'success');
                    break;

                case 'all_complete':
                    debugLog('All models completed');
                    if (data.hex_code) {
                        currentHexCode = data.hex_code;
                        loadConversations();
                    }
                    
                    // ✅ FIXED: Update token status dynamically WITHOUT disrupting conversation
                    if (data.tokens_left !== undefined) {
                        const tokensLeftElement = document.getElementById('tokens_left');
                        const tokensLeftElementMobile = document.getElementById('tokens_left_mobile');
                        if (tokensLeftElement) {
                            tokensLeftElement.textContent = data.tokens_left;
                        }
                        if (tokensLeftElementMobile) {
                            tokensLeftElementMobile.textContent = data.tokens_left;
                        }
                        
                        // ✅ Check if tokens dropped below threshold
                        if (data.has_low_tokens === true && !hasLowTokens) {
                            // Tokens just dropped below 5000!
                            console.warn('⚠️ Tokens dropped below 5000!', {
                                old_status: hasLowTokens,
                                new_status: true,
                                tokens_left: data.tokens_left,
                                current_conversation: currentConversationId
                            });
                            
                            // Update global variable
                            hasLowTokens = true;
                            
                            // Show notification
                            showNotification(`⚠️ Low tokens (${data.tokens_left.toLocaleString()}). New conversations will use default model only. Current chat continues.`, 'warning');
                            
                            // ✅ CRITICAL: Only update dropdown restrictions, DON'T regenerate panels
                            enforceTokenRestrictionsInDropdown();
                            
                            // ✅ Highlight token display
                            if (tokensLeftElement) {
                                tokensLeftElement.parentElement.classList.add('text-yellow-300', 'font-semibold');
                            }
                        }
                    }
                    
                    updateUserStats();
                    // Clear mappings after completion
                    modelIdMapping = {};
                    imageGenModelMapping = {};
                    break;
            }
        }

        function finalizeModelImageResponse(model, imageUrl, prompt) {
            console.log('🖼️ Finalizing image response', {
                model: model,
                imageUrl: imageUrl ? imageUrl.substring(0, 50) + '...' : 'null',
                hasPrompt: !!prompt,
                currentMode: currentOptimizationMode
            });

            let responseElement = modelResponseElements[model];
            
            // ✅ FIX: If responseElement doesn't exist, try to find it
            if (!responseElement) {
                console.warn(`No response element in cache for ${model}, trying to find it...`);
                
                // Try to find the conversation div
                const conversationDiv = document.getElementById(`conversation-${model}`);
                if (conversationDiv) {
                    // Look for the last assistant response
                    const lastResponse = conversationDiv.querySelector('.assistant-response:last-child .message-content');
                    if (lastResponse) {
                        responseElement = lastResponse;
                        modelResponseElements[model] = responseElement;
                        console.log(`✅ Found response element for ${model}`);
                    }
                }
                
                // Still not found? Try to find the panel itself
                if (!responseElement) {
                    const panel = document.querySelector(`[data-model-id="${model}"]`);
                    if (panel) {
                        const conv = panel.querySelector('.model-conversation');
                        if (conv) {
                            console.log(`✅ Found panel for ${model}, creating response container...`);
                            addMessageToConversation(model, 'assistant', '', false);
                            responseElement = modelResponseElements[model];
                        }
                    }
                }
            }
            
            if (!responseElement) {
                console.error(`❌ Could not find or create response element for model: ${model}`);
                console.log('Available panels:', Array.from(document.querySelectorAll('.model-panel')).map(p => p.dataset.modelId));
                console.log('Model response elements:', Object.keys(modelResponseElements));
                
                // Last resort: show alert
                alert(`Failed to display image for ${model}. Check console for details.`);
                return;
            }

            console.log(`✅ Response element ready for ${model}`);

            // Clear any existing content
            responseElement.innerHTML = '';
            
            // Create image container
            const imgContainer = document.createElement('div');
            imgContainer.className = 'my-4';
            
            // Create image element
            const img = document.createElement('img');
            img.src = imageUrl;
            img.alt = prompt || 'Generated image';
            img.className = 'rounded-lg max-w-full h-auto shadow-md cursor-pointer';
            
            // Add click handler for modal
            img.addEventListener('click', () => {
                openImageModal(imageUrl, prompt || 'Generated image');
            });
            
            // Add error handler
            img.onerror = function() {
                console.error('❌ Failed to load image:', imageUrl);
                responseElement.innerHTML = `
                    <div class="text-red-600 p-4 border border-red-300 rounded-lg bg-red-50">
                        <div class="flex items-start gap-3">
                            <i class="las la-exclamation-triangle text-2xl flex-shrink-0"></i>
                            <div>
                                <p class="font-semibold mb-1">Failed to load image</p>
                                <p class="text-sm">The generated image could not be loaded.</p>
                                <p class="text-xs mt-2 break-all">URL: ${imageUrl.substring(0, 100)}...</p>
                            </div>
                        </div>
                    </div>
                `;
            };
            
            // Add load handler for confirmation
            img.onload = function() {
                console.log('✅ Image loaded successfully for', model);
            };
            
            imgContainer.appendChild(img);
            
            // Add prompt text if provided
            if (prompt) {
                const promptText = document.createElement('p');
                promptText.className = 'text-sm text-gray-600 mt-2 italic';
                promptText.textContent = `Prompt: ${prompt}`;
                imgContainer.appendChild(promptText);
            }
            
            responseElement.appendChild(imgContainer);

            // Update conversation history
            if (conversationHistory[model] && conversationHistory[model].length > 0) {
                const lastEntry = conversationHistory[model][conversationHistory[model].length - 1];
                lastEntry.response = imageUrl;
                console.log('✅ Updated conversation history for', model);
            } else {
                console.warn('⚠️ No conversation history entry to update for', model);
            }

            // Scroll to show the image
            const conversationDiv = document.getElementById(`conversation-${model}`);
            if (conversationDiv) {
                conversationDiv.scrollTop = conversationDiv.scrollHeight;
            }
            
            console.log('✅ Image finalized for', model);
        }


        function isImageURL(str) {
            if (!str || typeof str !== 'string') return false;
            
            try {
                const url = new URL(str);
                const pathname = url.pathname.toLowerCase();
                const imageExtensions = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.bmp'];
                
                return imageExtensions.some(ext => pathname.endsWith(ext));
            } catch (e) {
                return false;
            }
        }

        function openImageModal(src, alt = '') {
            let modal = document.getElementById('image-modal');
            
            if (!modal) {
                const modalHTML = `
                    <div id="image-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80 hidden">
                        <div class="relative max-w-7xl max-h-screen p-4">
                            <img id="modal-image" class="max-h-screen max-w-full rounded-lg" />
                            <button id="modal-close" class="absolute top-2 right-2 text-white text-xl bg-black bg-opacity-50 px-3 py-1 rounded hover:bg-opacity-75">&times;</button>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                
                modal = document.getElementById('image-modal');
                
                document.getElementById('modal-close').addEventListener('click', () => {
                    modal.classList.add('hidden');
                });
                
                modal.addEventListener('click', (e) => {
                    if (e.target.id === 'image-modal') {
                        modal.classList.add('hidden');
                    }
                });
            }
            
            const modalImg = document.getElementById('modal-image');
            modalImg.src = src;
            modalImg.alt = alt;
            modal.classList.remove('hidden');
        }

        function updateModelStatus(model, status) {
            const statusElement = document.getElementById(`status-${model}`);
            if (statusElement) {
                statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                statusElement.className = `model-status ${status}`;
            }
        }

        // ✅ NEW: Helper function to create consistent user message HTML
        function createUserMessageHTML(content, messageId, filesOrFile = null, attachments = null) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'conversation-entry';
            
            const userDiv = document.createElement('div');
            userDiv.className = 'user-prompt';
            userDiv.dataset.messageId = messageId;
            userDiv.innerHTML = formatUserMessage(content);
            
            // Add attachment if present (for real-time with file)
            // ✅ UPDATED: Add attachments if present (for real-time with files array)
            if (filesOrFile) {
                // Handle both single file and array of files
                const filesArray = Array.isArray(filesOrFile) ? filesOrFile : [filesOrFile];
                
                filesArray.forEach(file => {
                    const fileType = file.type.toLowerCase();
                    const attachmentContainer = document.createElement('div');
                    
                    if (fileType.startsWith('image/')) {
                        // For images
                        attachmentContainer.className = 'mt-2 flex items-center gap-2';
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            attachmentContainer.innerHTML = `
                                <img src="${e.target.result}" 
                                    alt="${file.name}" 
                                    class="w-12 h-12 object-cover rounded cursor-pointer hover:opacity-80 transition border border-white/30"
                                    onclick="openImageModal('${e.target.result}', '${file.name}')"
                                    title="Click to view full size">
                                <a href="${e.target.result}" 
                                    download="${file.name}"
                                    class="text-xs text-white/70 hover:text-white flex-1 truncate underline decoration-dotted hover:decoration-solid transition-all" 
                                    title="Click to download: ${file.name}"
                                    onclick="event.stopPropagation()">
                                    ${file.name}
                                </a>
                            `;
                        };
                        reader.readAsDataURL(file);
                        userDiv.appendChild(attachmentContainer);
                    } else {
                        // For documents
                        attachmentContainer.className = 'mt-2 flex items-center gap-2 text-xs text-white/80';
                        
                        let icon = 'la-paperclip';
                        if (fileType.includes('pdf')) icon = 'la-file-pdf';
                        else if (fileType.includes('word') || fileType.includes('document')) icon = 'la-file-word';
                        
                        const displayName = file.name.length > 30 ? file.name.substring(0, 27) + '...' : file.name;
                        const fileUrl = URL.createObjectURL(file);
                        
                        attachmentContainer.innerHTML = `
                            <i class="las ${icon}"></i>
                            <a href="${fileUrl}" 
                                download="${file.name}"
                                class="truncate hover:text-white underline decoration-dotted hover:decoration-solid transition-all" 
                                title="Click to download: ${file.name}"
                                onclick="event.stopPropagation()">
                                ${displayName}
                            </a>
                        `;
                        userDiv.appendChild(attachmentContainer);
                    }
                });
            }
            
            // Add attachment if present (for history with attachment object)
            if (attachments && Array.isArray(attachments)) {
                attachments.forEach(attachment => {
                    const attachmentBadge = createAttachmentBadge(attachment);
                    userDiv.appendChild(attachmentBadge);
                });
            }
            
            messageDiv.appendChild(userDiv);
            messageDiv.appendChild(createMessageActions(messageId, false));
            
            return messageDiv;
        }

        function addMessageToConversation(model, role, content, isStreaming = false) {
            const conversationDiv = document.getElementById(`conversation-${model}`);
            if (!conversationDiv) {
                console.error('❌ Conversation div not found for model:', model);
                return;
            }

            const emptyState = conversationDiv.querySelector('.text-center');
            if (emptyState) {
                emptyState.remove();
            }

            if (role === 'user') {
                const messageId = `user-msg-${model}-${Date.now()}`;
               // ✅ FIXED: Use attachedFiles array instead of file input
                const files = attachedFiles.length > 0 ? [...attachedFiles] : null;

                // ✅ Use consistent helper function
                const messageDiv = createUserMessageHTML(content, messageId, files);
                conversationDiv.appendChild(messageDiv);
                
                if (!conversationHistory[model]) conversationHistory[model] = [];
                conversationHistory[model].push({
                    prompt: content,
                    response: ''
                });
            } else {
                // Assistant response code remains the same
                const messageDiv = document.createElement('div');
                messageDiv.className = 'conversation-entry';
                const responseId = `response-${model}-${Date.now()}`;
                const messageId = `assistant-msg-${model}-${Date.now()}`;
                
                messageDiv.innerHTML = `
                    <div class="assistant-response" data-message-id="${messageId}" data-model="${model}">
                        <div class="message-content" id="${responseId}">
                            ${isStreaming ? '<div class="thinking-indicator flex space-x-1"><div class="dot w-2 h-2 rounded-full"></div><div class="dot w-2 h-2 rounded-full"></div><div class="dot w-2 h-2 rounded-full"></div></div>' : ''}
                        </div>
                        <div class="message-actions">
                            <button class="message-action-btn copy-msg-btn" data-message-id="${messageId}" title="Copy Response">
                                <i class="las la-copy"></i>
                            </button>
                            <button class="message-action-btn read-msg-btn" data-message-id="${messageId}" title="Read Aloud">
                                <i class="las la-volume-up"></i>
                            </button>
                            <button class="message-action-btn regenerate-msg-btn" data-message-id="${messageId}" data-model="${model}" title="Regenerate Response">
                                <i class="las la-redo-alt"></i>
                            </button>
                            <button class="message-action-btn translate-msg-btn" data-message-id="${messageId}" title="Translate Response">
                                <i class="las la-language"></i>
                            </button>
                        </div>
                    </div>
                `;
                conversationDiv.appendChild(messageDiv);
                
                const responseElement = document.getElementById(responseId);
                modelResponseElements[model] = responseElement;
                
                if (!responseElement) {
                    console.error(`❌ FAILED to create response element for ${model}!`, {
                        responseId: responseId,
                        conversationDivExists: !!conversationDiv,
                        messageDivExists: !!messageDiv,
                        conversationDivHTML: conversationDiv ? conversationDiv.innerHTML.substring(0, 200) : 'null'
                    });
                } else {
                    console.log(`✅ Response element created for ${model}`, {
                        responseId: responseId,
                        elementExists: true,
                        isStreaming: isStreaming,
                        conversationDivId: conversationDiv.id
                    });
                }
            }

            conversationDiv.scrollTop = conversationDiv.scrollHeight;
        }

        function updateModelResponse(model, chunk, fullResponse) {
            const responseElement = modelResponseElements[model];
            if (!responseElement) return;

            if (fullResponse) {
                responseElement.innerHTML = '';
                responseElement.textContent = fullResponse;
                
                if (conversationHistory[model] && conversationHistory[model].length > 0) {
                    const lastEntry = conversationHistory[model][conversationHistory[model].length - 1];
                    lastEntry.response = fullResponse;
                }
            }

            const conversationDiv = document.getElementById(`conversation-${model}`);
            if (conversationDiv) {
                conversationDiv.scrollTop = conversationDiv.scrollHeight;
            }
        }

        function finalizeModelResponse(model, finalResponse) {
            debugLog(`Finalizing response for model: ${model}`, {
                responseLength: finalResponse.length,
                containsChart: finalResponse.includes('```chart')
            });

            const responseElement = modelResponseElements[model];
            if (!responseElement) {
                debugLog(`No response element found for model: ${model}`);
                return;
            }

            responseElement.innerHTML = '';
            responseElement.textContent = finalResponse;

            debugLog(`Processing charts for model: ${model}`);
            processMessageContent(responseElement);

            if (conversationHistory[model] && conversationHistory[model].length > 0) {
                const lastEntry = conversationHistory[model][conversationHistory[model].length - 1];
                lastEntry.response = finalResponse;
            }

            const conversationDiv = document.getElementById(`conversation-${model}`);
            if (conversationDiv) {
                conversationDiv.scrollTop = conversationDiv.scrollHeight;
            }
        }

        function resetUI() {
            sendButton.classList.remove('hidden');
            stopButton.classList.add('hidden');
            messageInput.disabled = false;
            optionsDropdownBtn.disabled = false;
            messageInput.focus();
            abortController = null;
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // ✅ NEW: Format user message with proper formatting
        function formatUserMessage(text) {
            if (!text) return '';
            
            // First escape HTML
            let formatted = escapeHtml(text);
            
            // Make URLs clickable
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            formatted = formatted.replace(urlRegex, (url) => {
                // Remove trailing punctuation that might be caught
                let cleanUrl = url;
                const trailingPunc = /[.,;:!?)]+$/.exec(url);
                let punctuation = '';
                if (trailingPunc) {
                    punctuation = trailingPunc[0];
                    cleanUrl = url.slice(0, -punctuation.length);
                }
                return `<a href="${cleanUrl}" target="_blank" rel="noopener noreferrer">${cleanUrl}</a>${punctuation}`;
            });
            
            // Make email addresses clickable
            const emailRegex = /([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)/g;
            formatted = formatted.replace(emailRegex, (email) => {
                return `<a href="mailto:${email}">${email}</a>`;
            });
            
            return formatted;
        }

        // Action button handlers
        document.addEventListener('click', (e) => {
            // Archive/Unarchive conversation
            if (e.target.closest('.archive-conversation-btn')) {
                e.stopPropagation(); // Prevent conversation from opening
                const btn = e.target.closest('.archive-conversation-btn');
                const hexCode = btn.dataset.hexCode;
                const isArchived = btn.dataset.archived === 'true';
                
                toggleArchiveConversation(hexCode, isArchived);
                return;
            }
            
            // Edit conversation title
            if (e.target.closest('.edit-conversation-btn')) {
                e.stopPropagation(); // Prevent conversation from opening
                const btn = e.target.closest('.edit-conversation-btn');
                const hexCode = btn.dataset.hexCode;
                const currentTitle = btn.closest('.conversation-item').querySelector('.conversation-title').textContent;
                const newTitle = prompt('Enter new title:', currentTitle);
                if (newTitle && newTitle.trim()) {
                    updateConversationTitle(hexCode, newTitle.trim());
                }
                return;
            }

            // Delete conversation
            if (e.target.closest('.delete-conversation-btn')) {
                e.stopPropagation(); // Prevent conversation from opening
                const btn = e.target.closest('.delete-conversation-btn');
                const hexCode = btn.dataset.hexCode;
                if (confirm('Are you sure you want to delete this conversation?')) {
                    deleteConversation(hexCode);
                }
                return;
            }

            // ✅ NEW: Export conversation
            if (e.target.closest('.export-conversation-btn')) {
                e.stopPropagation(); // Prevent conversation from opening
                const btn = e.target.closest('.export-conversation-btn');
                const hexCode = btn.dataset.hexCode;
                showExportDialog(hexCode);
                return;
            }

            // Conversation item click (load conversation)
            if (e.target.closest('.conversation-item')) {
                // Don't load conversation if we're clicking on action buttons or checkboxes
                if (e.target.closest('.archive-conversation-btn') ||
                    e.target.closest('.share-conversation-btn') ||
                    e.target.closest('.export-conversation-btn') ||
                    e.target.closest('.edit-conversation-btn') ||
                    e.target.closest('.delete-conversation-btn') ||
                    e.target.closest('.conversation-menu-button') ||
                    e.target.closest('.conversation-checkbox')) {
                    return;
                }
                
                const conversationItem = e.target.closest('.conversation-item');
                const hexCode = conversationItem.dataset.hexCode;
                if (hexCode) {
                    loadConversation(hexCode);
                }
                return;
            }

            // Close model button
            if (e.target.closest('.close-model-btn')) {
                const btn = e.target.closest('.close-model-btn');
                const modelId = btn.dataset.model;
                const modelData = selectedModels.find(m => m.model === modelId);
                
                if (modelData) {
                    const hasHistory = conversationHistory[modelId] && conversationHistory[modelId].length > 0;
                    const confirmMessage = hasHistory 
                        ? `Close ${modelData.displayName}? This will clear its conversation history.`
                        : `Close ${modelData.displayName}?`;
                    
                    if (confirm(confirmMessage)) {
                        let checkbox = null;
                        
                        // ✅ FIX: Handle different optimization modes
                        if (currentOptimizationMode === 'smart_same') {
                            // Extract provider name from panel ID (e.g., "openai_smart_panel" → "openai")
                            const provider = modelId.replace('_smart_panel', '');
                            checkbox = document.querySelector(`input.provider-checkbox[value="${provider}"]`);
                            console.log('Smart Same mode: Looking for provider checkbox:', provider, 'Found:', !!checkbox);
                        } else if (currentOptimizationMode === 'smart_all') {
                            // In Smart All mode, don't allow closing the single panel
                            alert('Cannot close the Auto-Best panel. Switch to Manual Select or Auto-Match mode to select specific models/providers.');
                            return;
                        } else {
                            // Fixed mode - look for model checkbox
                            checkbox = document.querySelector(`input.model-checkbox[value="${modelId}"]`);
                            console.log('Fixed mode: Looking for model checkbox:', modelId, 'Found:', !!checkbox);
                        }
                        
                        if (checkbox) {
                            checkbox.checked = false;
                            delete conversationHistory[modelId];
                            updateSelectedModels();
                            console.log('✅ Successfully closed panel:', modelId);
                        } else {
                            console.error('❌ Could not find checkbox for:', modelId, 'Mode:', currentOptimizationMode);
                            alert('Could not close this panel. Please try using the dropdown menu.');
                        }
                    }
                }
                return;
            }

            // Maximize model button
            if (e.target.closest('.maximize-model-btn')) {
                const btn = e.target.closest('.maximize-model-btn');
                const modelId = btn.dataset.model;
                const modelPanel = document.querySelector(`[data-model-id="${modelId}"]`);
                
                if (modelPanel.classList.contains('maximized')) {
                    minimizeModelPanel(modelPanel, btn);
                } else {
                    maximizeModelPanel(modelPanel, btn);
                }
                return;
            }
            
            // Copy response button
            if (e.target.classList.contains('copy-response-btn')) {
                const model = e.target.dataset.model;
                const history = conversationHistory[model];
                if (history && history.length > 0) {
                    const lastResponse = history[history.length - 1].response;
                    navigator.clipboard.writeText(lastResponse).then(() => {
                        const originalText = e.target.innerHTML;
                        e.target.innerHTML = '✓ Copied!';
                        setTimeout(() => {
                            e.target.innerHTML = originalText;
                        }, 2000);
                    });
                }
                return;
            }
            
            // Read aloud button
            if (e.target.classList.contains('read-aloud-btn')) {
                const model = e.target.dataset.model;
                const history = conversationHistory[model];
                if (history && history.length > 0) {
                    const lastResponse = history[history.length - 1].response;
                    
                    if (window.speechSynthesis.speaking) {
                        window.speechSynthesis.cancel();
                        e.target.innerHTML = '🔊 Read';
                        return;
                    }
                    
                    const speech = new SpeechSynthesisUtterance(lastResponse);
                    speech.rate = 1;
                    speech.pitch = 1;
                    speech.volume = 1;
                    
                    e.target.innerHTML = '⏹ Stop';
                    
                    speech.onend = () => {
                        e.target.innerHTML = '🔊 Read';
                    };
                    
                    window.speechSynthesis.speak(speech);
                }
                return;
            }

            // Clear button
            if (e.target.classList.contains('clear-btn')) {
                const model = e.target.dataset.model;
                const modelData = selectedModels.find(m => m.model === model);
                if (modelData && confirm(`Clear conversation history for ${modelData.displayName}?`)) {
                    conversationHistory[model] = [];
                    const conversationDiv = document.getElementById(`conversation-${model}`);
                    if (conversationDiv) {
                        conversationDiv.innerHTML = getEmptyStateHTML();
                    }
                }
                return;
            }
        });

       // ✅ ENHANCED: Maximize model panel function with full-screen chat
        function maximizeModelPanel(panel, btn) {
            const modelId = btn.dataset.model;
            const modelData = selectedModels.find(m => m.model === modelId);
            
            if (!modelData) return;
            
            // Hide all other panels
            const allPanels = document.querySelectorAll('.model-panel');
            allPanels.forEach(p => {
                if (p !== panel) {
                    p.classList.add('hidden-panel');
                }
            });
            
            // Maximize this panel
            panel.classList.add('maximized');
            modelsContainer.classList.add('has-maximized');
            
            // Hide main chat input
            const mainChatForm = document.getElementById('compare-form');
            mainChatForm.classList.add('hidden-on-maximize');
            
            // Create maximized header overlay
            const headerOverlay = document.createElement('div');
            headerOverlay.className = 'maximized-header-overlay';
            headerOverlay.id = 'maximized-header-overlay';
            headerOverlay.innerHTML = `
                <div class="model-name flex items-center gap-2">
                    ${getProviderIcon(modelData.provider)}
                    <span>${modelData.displayName}</span>
                </div>
                <div id="maximized-status-indicator" class="hidden flex items-center gap-2 bg-white/20 px-4 py-2 rounded-lg">
                    <div class="animate-pulse flex items-center gap-2">
                        <div class="flex gap-1">
                            <div class="w-2 h-2 bg-white rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                            <div class="w-2 h-2 bg-white rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                            <div class="w-2 h-2 bg-white rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                        </div>
                        <span class="text-sm font-medium">Generating response...</span>
                    </div>
                </div>
                <div class="close-maximize-btn" onclick="minimizeFromOverlay('${modelId}')">
                    <i class="las la-compress"></i>
                    <span>Exit Full Screen (ESC)</span>
                </div>
            `;
            document.body.appendChild(headerOverlay);
            
            // Create maximized chat input (clone the main form)
            const maximizedInput = document.createElement('div');
            maximizedInput.className = 'maximized-chat-input';
            maximizedInput.id = 'maximized-chat-input';
            maximizedInput.innerHTML = `
                <form id="maximized-compare-form" class="max-w-6xl mx-auto">
                    <!-- File Upload Preview -->
                    <div id="maximized-attachment-preview" class="hidden bg-gray-100 p-3 rounded-lg mb-3 inline-block max-w-max">
                        <div class="flex items-center space-x-3">
                            <i class="las la-paperclip text-gray-700 text-xl"></i>
                            <div id="maximized-file-name" class="text-gray-700 text-sm"></div>
                            <div class="flex items-center space-x-2 ml-auto">
                                <button type="button" id="maximized-preview-attachment-btn" class="text-gray-600 hover:text-gray-800 bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded transition-colors text-sm">
                                    <i class="las la-eye mr-1"></i>Preview
                                </button>
                                <button type="button" id="maximized-remove-file" class="text-red-600 hover:text-red-700 bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded transition-colors">
                                    <i class="las la-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Main Input Area -->
                    <div class="flex items-end space-x-3">
                        <!-- Left Side: Input with inline controls -->
                        <div class="flex-1 relative">
                            <div class="flex items-end bg-white border-2 border-purple-200 rounded-lg focus-within:ring-2 focus-within:ring-purple-500">
                                <!-- Textarea -->
                                <textarea 
                                    id="maximized-message-input" 
                                    name="message" 
                                    placeholder="Type your message here..." 
                                    class="flex-1 p-3 bg-transparent text-gray-800 placeholder-gray-400 focus:outline-none resize-none min-h-[52px] max-h-[200px]"
                                    rows="1"
                                    required></textarea>
                                
                                <!-- Inline Controls -->
                                <div class="flex items-center space-x-2 p-2 pb-3">
                                    <!-- Attachment Button -->
                                    <label for="maximized-file-input" class="cursor-pointer text-gray-500 hover:text-purple-600 transition-colors" title="Attach file">
                                        <i class="las la-paperclip text-xl"></i>
                                    </label>
                                    <!-- NEW -->
                                    <input type="file" id="maximized-file-input" name="files[]" class="hidden" 
                                        accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.webp,.gif" multiple>
                                    
                                    <!-- Options Dropdown Button -->
                                    <div class="relative">
                                        <button type="button" id="maximized-options-dropdown-btn" class="text-gray-500 hover:text-purple-600 transition-colors" title="More options">
                                            <i class="las la-sliders-h text-xl"></i>
                                        </button>
                                        
                                        <!-- Dropdown Menu -->
                                        <div id="maximized-options-dropdown" class="hidden absolute bottom-full right-0 mb-2 bg-white rounded-lg shadow-lg py-2 min-w-[200px] z-50">
                                            <label class="flex items-center space-x-3 px-4 py-2 hover:bg-gray-100 cursor-pointer transition-colors">
                                                <input type="checkbox" id="maximized-web-search" name="web_search" class="text-purple-600 focus:ring-purple-500 rounded">
                                                <div class="flex items-center space-x-2">
                                                    <i class="las la-search text-gray-700"></i>
                                                    <span class="text-gray-700 text-sm">Web Search</span>
                                                </div>
                                            </label>
                                            
                                            <label class="flex items-center space-x-3 px-4 py-2 hover:bg-gray-100 cursor-pointer transition-colors" id="maximized-create-image-label">
                                                <input type="checkbox" id="maximized-create-image" name="create_image" class="text-purple-600 focus:ring-purple-500 rounded">
                                                <div class="flex items-center space-x-2">
                                                    <i class="las la-image text-gray-700"></i>
                                                    <span class="text-gray-700 text-sm">Generate Image</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side: Action Buttons -->
                        <div class="flex flex-col space-y-2">
                            <button type="submit" id="maximized-send-button" 
                                    class="bg-purple-600 hover:bg-purple-700 text-white p-3 rounded-lg font-medium transition-colors flex items-center justify-center min-w-[52px] min-h-[52px]">
                                <i class="las la-paper-plane text-xl"></i>
                            </button>
                            
                            <button type="button" id="maximized-stop-button" 
                                    class="hidden bg-red-600 hover:bg-red-700 text-white p-3 rounded-lg font-medium transition-colors flex items-center justify-center min-w-[52px] min-h-[52px]">
                                <i class="las la-stop text-xl"></i>
                            </button>
                        </div>
                    </div>
                    
                </form>
            `;
            document.body.appendChild(maximizedInput);
            
            // Initialize maximized input handlers
            initializeMaximizedInputHandlers(modelId);
            
            // Change icon to minimize
            const icon = btn.querySelector('i');
            icon.classList.remove('la-expand');
            icon.classList.add('la-compress');
            btn.title = 'Exit Full Screen';
            
            // Scroll to bottom of conversation
            const conversationDiv = panel.querySelector('.model-response');
            if (conversationDiv) {
                setTimeout(() => {
                    conversationDiv.scrollTop = conversationDiv.scrollHeight;
                }, 100);
            }
            
            // Focus on input
            setTimeout(() => {
                const input = document.getElementById('maximized-message-input');
                if (input) input.focus();
            }, 100);
            
            // Add backdrop click handler
            setTimeout(() => {
                modelsContainer.addEventListener('click', handleBackdropClick);
            }, 100);
        }

        // ✅ ENHANCED: Minimize model panel function
        function minimizeModelPanel(panel, btn) {
            // Show all panels
            const allPanels = document.querySelectorAll('.model-panel');
            allPanels.forEach(p => {
                p.classList.remove('hidden-panel');
            });
            
            // Minimize this panel
            panel.classList.remove('maximized');
            modelsContainer.classList.remove('has-maximized');
            
            // Show main chat input
            const mainChatForm = document.getElementById('compare-form');
            mainChatForm.classList.remove('hidden-on-maximize');
            
            // Remove maximized header overlay
            const headerOverlay = document.getElementById('maximized-header-overlay');
            if (headerOverlay) {
                headerOverlay.remove();
            }
            
            // Remove maximized chat input
            const maximizedInput = document.getElementById('maximized-chat-input');
            if (maximizedInput) {
                maximizedInput.remove();
            }
            
            // Change icon back to maximize
            const icon = btn.querySelector('i');
            icon.classList.remove('la-compress');
            icon.classList.add('la-expand');
            btn.title = 'Maximize';
            
            // Remove backdrop click handler
            modelsContainer.removeEventListener('click', handleBackdropClick);
        }

        // ✅ NEW: Global function to minimize from overlay
        window.minimizeFromOverlay = function(modelId) {
            const panel = document.querySelector(`[data-model-id="${modelId}"]`);
            const btn = panel?.querySelector('.maximize-model-btn');
            if (panel && btn) {
                minimizeModelPanel(panel, btn);
            }
        };

        // ✅ NEW: Initialize maximized input handlers
        function initializeMaximizedInputHandlers(currentModelId) {
            const input = document.getElementById('maximized-message-input');
            const fileInput = document.getElementById('maximized-file-input');
            const attachmentPreview = document.getElementById('maximized-attachment-preview');
            const fileNameSpan = document.getElementById('maximized-file-name');
            const removeFileButton = document.getElementById('maximized-remove-file');
            const previewAttachmentBtn = document.getElementById('maximized-preview-attachment-btn');
            const form = document.getElementById('maximized-compare-form');
            const sendButton = document.getElementById('maximized-send-button');
            const stopButton = document.getElementById('maximized-stop-button');
            const optionsDropdownBtn = document.getElementById('maximized-options-dropdown-btn');
            const optionsDropdown = document.getElementById('maximized-options-dropdown');
            const createImageCheckbox = document.getElementById('maximized-create-image');
            const createImageLabel = document.getElementById('maximized-create-image-label');
            
            let maximizedAttachmentFile = null;

            // ✅ Check if current model supports web search
            const modelCheckbox = document.querySelector(`input.model-checkbox[value="${currentModelId}"]`);
            let supportsWebSearch = false;
            
            if (modelCheckbox) {
                supportsWebSearch = modelCheckbox.dataset.supportsWebSearch === '1';
            } else {
                // For smart modes, extract provider and check
                const provider = currentModelId.replace('_smart_panel', '');
                const providerModels = document.querySelectorAll(`input.model-checkbox[data-provider="${provider}"]`);
                supportsWebSearch = Array.from(providerModels).some(cb => cb.dataset.supportsWebSearch === '1');
            }
            
            const maximizedWebSearchLabel = document.getElementById('maximized-create-image-label').previousElementSibling;
            const maximizedWebSearchCheckbox = document.getElementById('maximized-web-search');
            
            if (maximizedWebSearchLabel && maximizedWebSearchCheckbox) {
                if (supportsWebSearch) {
                    maximizedWebSearchLabel.classList.remove('opacity-50', 'cursor-not-allowed');
                    maximizedWebSearchLabel.title = 'Search the web for current information';
                    maximizedWebSearchCheckbox.disabled = false;
                } else {
                    maximizedWebSearchLabel.classList.add('opacity-50', 'cursor-not-allowed');
                    maximizedWebSearchLabel.title = 'This model does not support web search';
                    maximizedWebSearchCheckbox.disabled = true;
                    maximizedWebSearchCheckbox.checked = false;
                }
            }
            
            // ✅ Check if current model supports image generation
            const supportsImageGen = modelSupportsImageGen(currentModelId);
            if (createImageLabel) {
                if (supportsImageGen) {
                    createImageLabel.classList.remove('opacity-50', 'cursor-not-allowed');
                    createImageLabel.title = 'Generate images with this model';
                    createImageCheckbox.disabled = false;
                } else {
                    createImageLabel.classList.add('opacity-50', 'cursor-not-allowed');
                    createImageLabel.title = 'This model does not support image generation';
                    createImageCheckbox.disabled = true;
                    createImageCheckbox.checked = false;
                }
            }

            // ✅ NEW: Handle file paste in maximized input (images and other files)
            input.addEventListener('paste', async (e) => {
                const items = e.clipboardData?.items;
                if (!items) return;

                // Look for file in clipboard (images or other files)
                for (let i = 0; i < items.length; i++) {
                    const item = items[i];

                    // Check if item is a file (image or document)
                    if (item.kind === 'file') {
                        const blob = item.getAsFile();
                        if (!blob) continue;

                        // Validate file size (10MB max)
                        const maxSize = 10 * 1024 * 1024; // 10MB
                        if (blob.size > maxSize) {
                            showNotification('File too large. Maximum size is 10MB', 'error');
                            continue;
                        }

                        // Allowed file types
                        const allowedTypes = [
                            'image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif',
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        ];

                        if (!allowedTypes.includes(blob.type)) {
                            showNotification('File type not supported. Allowed: PDF, DOC, DOCX, PNG, JPG, JPEG, WEBP, GIF', 'error');
                            continue;
                        }

                        e.preventDefault(); // Prevent default paste behavior for files

                        console.log('File pasted in maximized mode:', {
                            type: blob.type,
                            size: blob.size,
                            name: blob.name
                        });

                        // Create a File object with proper name
                        const timestamp = Date.now();
                        let extension = blob.type.split('/')[1] || 'bin';
                        if (extension.includes('vnd.openxmlformats')) extension = 'docx';
                        if (extension.includes('msword')) extension = 'doc';

                        const fileName = blob.type.startsWith('image/')
                            ? `pasted-image-${timestamp}.${extension}`
                            : `pasted-file-${timestamp}.${extension}`;

                        const file = new File([blob], fileName, {
                            type: blob.type
                        });

                        // Set the file as current attachment
                        maximizedAttachmentFile = file;

                        // Update file input
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        fileInput.files = dataTransfer.files;

                        // Show preview
                        fileNameSpan.innerHTML = '';
                        const fileType = file.type.toLowerCase();

                        if (fileType.startsWith('image/')) {
                            // Image preview
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.className = 'max-h-20 max-w-32 rounded border';
                                fileNameSpan.appendChild(img);
                            };
                            reader.readAsDataURL(file);
                        } else {
                            // File icon preview
                            const fileInfo = document.createElement('div');
                            fileInfo.className = 'flex items-center space-x-2';

                            let icon = 'la-file';
                            if (fileType.includes('pdf')) icon = 'la-file-pdf';
                            else if (fileType.includes('word') || fileType.includes('document')) icon = 'la-file-word';

                            const fileSize = (file.size / 1024).toFixed(2) + ' KB';
                            const displayName = file.name.length > 30 ? file.name.substring(0, 27) + '...' : file.name;

                            fileInfo.innerHTML = `
                                <i class="las ${icon} text-xl"></i>
                                <div>
                                    <div class="font-medium text-sm" title="${file.name}">${displayName}</div>
                                    <div class="text-xs opacity-75">${fileSize}</div>
                                </div>
                            `;
                            fileNameSpan.appendChild(fileInfo);
                        }

                        attachmentPreview.classList.remove('hidden');

                        const fileTypeName = fileType.startsWith('image/') ? 'Image' : 'File';
                        showNotification(`📎 ${fileTypeName} pasted successfully`, 'success');

                        break;
                    }
                }
            });

            // ✅ NEW: Also handle drag and drop in maximized mode
            input.parentElement.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                input.parentElement.classList.add('border-purple-400', 'bg-purple-50');
            });

            input.parentElement.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                input.parentElement.classList.remove('border-purple-400', 'bg-purple-50');
            });

            input.parentElement.addEventListener('drop', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                input.parentElement.classList.remove('border-purple-400', 'bg-purple-50');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const file = files[0];
                    
                    if (file.type.startsWith('image/')) {
                        maximizedAttachmentFile = file;
                        
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        fileInput.files = dataTransfer.files;
                        
                        fileNameSpan.innerHTML = '';
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'max-h-20 max-w-32 rounded border';
                            fileNameSpan.appendChild(img);
                        };
                        reader.readAsDataURL(file);
                        
                        attachmentPreview.classList.remove('hidden');
                        showNotification('📎 Image dropped successfully', 'success');
                    } else {
                        showNotification('⚠️ Please drop an image file', 'warning');
                    }
                }
            });
            
            // Auto-resize textarea
            input.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 200) + 'px';
            });
            
            // Options dropdown toggle
            optionsDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                optionsDropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!optionsDropdownBtn.contains(e.target) && !optionsDropdown.contains(e.target)) {
                    optionsDropdown.classList.add('hidden');
                }
            });
            
            // File input change
            fileInput.addEventListener('change', async function(e) {
                const file = e.target.files[0];
                if (file) {
                    maximizedAttachmentFile = file;
                    fileNameSpan.innerHTML = '';
                    
                    const fileType = file.type.toLowerCase();
                    const fileName = file.name;
                    const fileSize = (file.size / 1024).toFixed(2) + ' KB';
                    
                    if (fileType.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(file);
                        img.onload = () => URL.revokeObjectURL(img.src);
                        img.className = 'max-h-20 max-w-32 rounded border';
                        fileNameSpan.appendChild(img);
                    } else {
                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'flex items-center space-x-2';
                        
                        let icon = 'la-file';
                        if (fileType.includes('pdf')) icon = 'la-file-pdf';
                        else if (fileType.includes('word') || fileType.includes('document')) icon = 'la-file-word';
                        
                        const displayName = fileName.length > 30 ? fileName.substring(0, 27) + '...' : fileName;
                        
                        fileInfo.innerHTML = `
                            <i class="las ${icon} text-xl"></i>
                            <div>
                                <div class="font-medium text-sm" title="${fileName}">${displayName}</div>
                                <div class="text-xs opacity-75">${fileSize}</div>
                            </div>
                        `;
                        fileNameSpan.appendChild(fileInfo);
                    }
                    attachmentPreview.classList.remove('hidden');
                }
            });
            
            // Remove file
            removeFileButton.addEventListener('click', function() {
                fileInput.value = '';
                maximizedAttachmentFile = null;
                attachmentPreview.classList.add('hidden');
            });
            
            // Preview attachment
            previewAttachmentBtn.addEventListener('click', async function() {
                if (maximizedAttachmentFile) {
                    await openAttachmentPreview(maximizedAttachmentFile);
                }
            });
            
            // Form submission
            // Inside initializeMaximizedInputHandlers, find the form submission handler:
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                    
                const message = input.value.trim();
                if (!message) {
                    alert('Please enter a message.');
                    return;
                }

                const webSearchChecked = document.getElementById('maximized-web-search').checked;
                const createImageChecked = document.getElementById('maximized-create-image').checked;
                
                // Create FormData
                const formData = new FormData();
                formData.append('message', message);
                
                // ✅ Handle model selection based on optimization mode
                if (currentOptimizationMode === 'smart_all') {
                    formData.append('models', JSON.stringify(['smart_all_auto']));
                } else if (currentOptimizationMode === 'smart_same') {
                    // ✅ FIXED: For Smart (Same), send the first model from the provider
                    const provider = currentModelId.replace('_smart_panel', '');
                    const providerCheckbox = document.querySelector(`input.provider-checkbox[value="${provider}"]`);
                    const firstModel = providerCheckbox ? providerCheckbox.dataset.firstModel : currentModelId;
                    formData.append('models', JSON.stringify([firstModel]));
                } else {
                    formData.append('models', JSON.stringify([currentModelId]));
                }
                
                formData.append('web_search', webSearchChecked ? '1' : '0');
                formData.append('create_image', createImageChecked ? '1' : '0');
                formData.append('optimization_mode', currentOptimizationMode);
                
                if (currentHexCode) {
                    formData.append('hex_code', currentHexCode);
                }
                
                if (maximizedAttachmentFile) {
                    formData.append('pdf', maximizedAttachmentFile);
                }
                
                // ... rest remains the same
                        
                // Disable inputs
                sendButton.classList.add('hidden');
                stopButton.classList.remove('hidden');
                input.disabled = true;
                optionsDropdownBtn.disabled = true;

                // Show status indicator in header
                const statusIndicator = document.getElementById('maximized-status-indicator');
                if (statusIndicator) {
                    statusIndicator.classList.remove('hidden');
                }

                // Add message to conversation
                addMessageToConversation(currentModelId, 'user', message);
                updateModelStatus(currentModelId, 'waiting');
                
                // Clear form
                input.value = '';
                input.style.height = 'auto';
                fileInput.value = '';
                maximizedAttachmentFile = null;
                attachmentPreview.classList.add('hidden');

                // ✅ NEW: Auto-uncheck create image checkbox after submission
                document.getElementById('maximized-create-image').checked = false;
                document.getElementById('maximized-web-search').checked = false; // Optional: also uncheck web search
                
                // Send request
                try {
                    abortController = new AbortController();
                    
                    const response = await fetch('{{ route("chat.multi-compare") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        signal: abortController.signal
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';
                    
                    function readStream() {
                        return reader.read().then(({ done, value }) => {
                            if (done) {
                                resetMaximizedUI();
                                return;
                            }
                            
                            buffer += decoder.decode(value, { stream: true });
                            const lines = buffer.split('\n');
                            buffer = lines.pop();
                            
                            lines.forEach(line => {
                                if (line.trim() && line.startsWith('data: ')) {
                                    const data = line.slice(6);
                                    if (data === '[DONE]') {
                                        resetMaximizedUI();
                                        return;
                                    }
                                    
                                    try {
                                        const parsed = JSON.parse(data);
                                        handleStreamMessage(parsed);
                                    } catch (e) {
                                        console.error('Error parsing JSON:', e, data);
                                    }
                                }
                            });
                            
                            return readStream();
                        });
                    }
                    
                    readStream();
                    
                } catch (error) {
                    if (error.name === 'AbortError') {
                        console.log('Request aborted');
                    } else {
                        console.error('Error:', error);
                        alert('An error occurred: ' + error.message);
                    }
                    resetMaximizedUI();
                }
            });
            
            // Stop button
            stopButton.addEventListener('click', () => {
                if (abortController) {
                    abortController.abort();
                }
            });
            
           
            
            function resetMaximizedUI() {
                sendButton.classList.remove('hidden');
                stopButton.classList.add('hidden');
                input.disabled = false;
                optionsDropdownBtn.disabled = false;
                input.focus();
                abortController = null;

                // Hide status indicator in header
                const statusIndicator = document.getElementById('maximized-status-indicator');
                if (statusIndicator) {
                    statusIndicator.classList.add('hidden');
                }
            }
        }

        // ✅ NEW: Handle backdrop click to minimize
        function handleBackdropClick(e) {
            // Only minimize if clicking on the backdrop (models-container itself), not on any child
            if (e.target === modelsContainer) {
                const maximizedPanel = document.querySelector('.model-panel.maximized');
                if (maximizedPanel) {
                    const btn = maximizedPanel.querySelector('.maximize-model-btn');
                    if (btn) {
                        minimizeModelPanel(maximizedPanel, btn);
                    }
                }
            }
        }

        // ✅ NEW: Handle file paste in main input (images and other files)
        messageInput.addEventListener('paste', async (e) => {
            const items = e.clipboardData?.items;
            if (!items) return;

            // Look for file in clipboard (images or other files)
            for (let i = 0; i < items.length; i++) {
                const item = items[i];

                // Check if item is a file (image or document)
                if (item.kind === 'file') {
                    const blob = item.getAsFile();
                    if (!blob) continue;

                    // Validate file size (10MB max)
                    const maxSize = 10 * 1024 * 1024; // 10MB
                    if (blob.size > maxSize) {
                        showNotification('File too large. Maximum size is 10MB', 'error');
                        continue;
                    }

                    // Allowed file types
                    const allowedTypes = [
                        'image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ];

                    if (!allowedTypes.includes(blob.type)) {
                        showNotification('File type not supported. Allowed: PDF, DOC, DOCX, PNG, JPG, JPEG, WEBP, GIF', 'error');
                        continue;
                    }

                    e.preventDefault(); // Prevent default paste behavior for files

                    console.log('File pasted from clipboard:', {
                        type: blob.type,
                        size: blob.size,
                        name: blob.name
                    });

                    // Create a File object with proper name
                    const timestamp = Date.now();
                    let extension = blob.type.split('/')[1] || 'bin';
                    if (extension.includes('vnd.openxmlformats')) extension = 'docx';
                    if (extension.includes('msword')) extension = 'doc';

                    const fileName = blob.type.startsWith('image/')
                        ? `pasted-image-${timestamp}.${extension}`
                        : `pasted-file-${timestamp}.${extension}`;

                    const file = new File([blob], fileName, {
                        type: blob.type
                    });

                    // Check if we've reached max files limit
                    if (attachedFiles.length >= MAX_FILES) {
                        showNotification(`Maximum ${MAX_FILES} files allowed`, 'error');
                        continue;
                    }

                    // Add file to attachedFiles array
                    attachedFiles.push(file);

                    // Set the file as current attachment (for backwards compatibility)
                    currentAttachmentFile = file;

                    // Update file preview to show all attached files
                    updateFilePreview();
                    attachmentPreview.classList.remove('hidden');

                    // Show notification
                    const fileTypeName = blob.type.startsWith('image/') ? 'Image' : 'File';
                    showNotification(`📎 ${fileTypeName} pasted successfully`, 'success');

                    break; // Only handle first file
                }
            }
        });

        // ✅ NEW: Also handle drag and drop for images
        messageInput.parentElement.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            messageInput.parentElement.classList.add('border-purple-400', 'bg-purple-50');
        });

        messageInput.parentElement.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            messageInput.parentElement.classList.remove('border-purple-400', 'bg-purple-50');
        });

        messageInput.parentElement.addEventListener('drop', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            messageInput.parentElement.classList.remove('border-purple-400', 'bg-purple-50');

            const droppedFiles = Array.from(e.dataTransfer.files);
            if (droppedFiles.length === 0) return;

            // Check if adding these files would exceed the limit
            if (attachedFiles.length + droppedFiles.length > MAX_FILES) {
                showNotification(`Maximum ${MAX_FILES} files allowed`, 'error');
                return;
            }

            // Validate all files before adding
            const validFiles = [];
            const allowedTypes = [
                'image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            for (const file of droppedFiles) {
                // Validate file size (10MB max)
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    showNotification(`File "${file.name}" is too large. Maximum size is 10MB`, 'error');
                    continue;
                }

                // Validate file type
                if (!allowedTypes.includes(file.type)) {
                    showNotification(`File "${file.name}" type not supported. Allowed: PDF, DOC, DOCX, PNG, JPG, JPEG, WEBP, GIF`, 'error');
                    continue;
                }

                validFiles.push(file);
            }

            if (validFiles.length > 0) {
                // Add files to attachedFiles array
                attachedFiles = [...attachedFiles, ...validFiles];

                // Set the first file as current attachment (for backwards compatibility)
                currentAttachmentFile = validFiles[0];

                // Update file preview to show all attached files
                updateFilePreview();
                attachmentPreview.classList.remove('hidden');

                const fileTypeName = validFiles.length === 1
                    ? (validFiles[0].type.startsWith('image/') ? 'Image' : 'File')
                    : 'Files';
                showNotification(`📎 ${validFiles.length} ${fileTypeName} dropped successfully`, 'success');
            }
        });

        // Stop button handler
        stopButton.addEventListener('click', () => {
            if (abortController) {
                abortController.abort();
            }
        });

        // ✅ NEW: ESC key to minimize maximized panel
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const maximizedPanel = document.querySelector('.model-panel.maximized');
                if (maximizedPanel) {
                    const btn = maximizedPanel.querySelector('.maximize-model-btn');
                    if (btn) {
                        minimizeModelPanel(maximizedPanel, btn);
                    }
                }
            }
        });

        // ====== SELECTION MODE & BULK ACTIONS ======
        let selectionMode = false;
        let selectedConversations = new Set();

        // Toggle selection mode
        document.getElementById('toggle-select-mode').addEventListener('click', () => {
            selectionMode = !selectionMode;
            
            if (selectionMode) {
                enableSelectionMode();
            } else {
                disableSelectionMode();
            }
        });

        function enableSelectionMode() {
            selectionMode = true;
            selectedConversations.clear();
            
            // Update UI
            document.getElementById('bulk-actions-bar').classList.remove('hidden');
            document.getElementById('toggle-select-mode').classList.add('bg-purple-600', 'text-white', 'border-purple-600');
            
            // Reset select all checkbox
            document.getElementById('select-all-checkbox').checked = false;
            
            // Add checkboxes to all conversation items
            document.querySelectorAll('.conversation-item').forEach(item => {
                if (!item.querySelector('.conversation-checkbox')) {
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'conversation-checkbox mr-3 h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded cursor-pointer';
                    checkbox.dataset.hexCode = item.dataset.hexCode;
                    
                    checkbox.addEventListener('click', (e) => {
                        e.stopPropagation(); // Prevent conversation from opening
                    });
                    
                    checkbox.addEventListener('change', (e) => {
                        handleCheckboxChange(item.dataset.hexCode, checkbox.checked);
                    });
                    
                    // Insert checkbox at the beginning
                    const firstChild = item.querySelector('.flex');
                    firstChild.insertBefore(checkbox, firstChild.firstChild);
                }
            });
            
            updateSelectedCount();
        }

        function disableSelectionMode() {
            selectionMode = false;
            selectedConversations.clear();
            
            // Update UI
            document.getElementById('bulk-actions-bar').classList.add('hidden');
            document.getElementById('toggle-select-mode').classList.remove('bg-purple-600', 'text-white', 'border-purple-600');
            
            // Remove all checkboxes
            document.querySelectorAll('.conversation-checkbox').forEach(checkbox => {
                checkbox.remove();
            });
            
            // Reset select all checkbox
            document.getElementById('select-all-checkbox').checked = false;
            
            updateSelectedCount();
        }

        function handleCheckboxChange(hexCode, isChecked) {
            if (isChecked) {
                selectedConversations.add(hexCode);
            } else {
                selectedConversations.delete(hexCode);
            }
            updateSelectedCount();
            updateSelectAllCheckbox();
        }

        function updateSelectedCount() {
            const count = selectedConversations.size;
            document.getElementById('bulk-selected-count').textContent = count; // ✅ CHANGED
            
            // Enable/disable bulk action buttons
            const bulkArchiveBtn = document.getElementById('bulk-archive-btn');
            const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
            
            if (count > 0) {
                bulkArchiveBtn.disabled = false;
                bulkDeleteBtn.disabled = false;
            } else {
                bulkArchiveBtn.disabled = true;
                bulkDeleteBtn.disabled = true;
            }
        }

        // Update select all checkbox state based on individual selections
        function updateSelectAllCheckbox() {
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const allCheckboxes = document.querySelectorAll('.conversation-checkbox');
            const totalConversations = allCheckboxes.length;
            const selectedCount = selectedConversations.size;
            
            if (selectedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (selectedCount === totalConversations) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        // Select All functionality
        document.getElementById('select-all-checkbox').addEventListener('change', function(e) {
            e.stopPropagation();
            const isChecked = this.checked;
            
            // Select or deselect all conversation checkboxes
            document.querySelectorAll('.conversation-checkbox').forEach(checkbox => {
                checkbox.checked = isChecked;
                handleCheckboxChange(checkbox.dataset.hexCode, isChecked);
            });
        });

        // Prevent select all label from bubbling
        document.querySelector('label[for="select-all-checkbox"]')?.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Cancel selection
        document.getElementById('cancel-select-btn').addEventListener('click', () => {
            disableSelectionMode();
        });

        // Bulk delete
        document.getElementById('bulk-delete-btn').addEventListener('click', async () => {
            if (selectedConversations.size === 0) return;
            
            const count = selectedConversations.size;
            if (!confirm(`Are you sure you want to delete ${count} conversation(s)? This action cannot be undone.`)) {
                return;
            }
            
            try {
                const response = await fetch('{{ route("bulk-delete-multi-compare-conversations") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        hex_codes: Array.from(selectedConversations)
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to delete conversations');
                }
                
                const data = await response.json();
                showNotification(data.message, 'success');
                
                // Reload conversations
                loadConversations();
                disableSelectionMode();
                
            } catch (error) {
                console.error('Bulk delete error:', error);
                showNotification('Failed to delete conversations', 'error');
            }
        });

        // Bulk archive
        document.getElementById('bulk-archive-btn').addEventListener('click', async () => {
            if (selectedConversations.size === 0) return;
            
            const count = selectedConversations.size;
            const currentFilter = document.getElementById('archive-filter').value;
            const isArchiving = currentFilter !== 'archived';
            
            const action = isArchiving ? 'archive' : 'unarchive';
            if (!confirm(`Are you sure you want to ${action} ${count} conversation(s)?`)) {
                return;
            }
            
            try {
                const response = await fetch('{{ route("bulk-archive-multi-compare-conversations") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        hex_codes: Array.from(selectedConversations),
                        archive: isArchiving
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to archive conversations');
                }
                
                const data = await response.json();
                showNotification(data.message, 'success');
                
                // Reload conversations
                loadConversations();
                disableSelectionMode();
                
            } catch (error) {
                console.error('Bulk archive error:', error);
                showNotification('Failed to archive conversations', 'error');
            }
        });

        // ====== ARCHIVE FILTER ======
        document.getElementById('archive-filter').addEventListener('change', function() {
            loadConversations();
        });

        // ====== PAGINATION VARIABLES ======
        let currentPage = 1;
        const itemsPerPage = 10;
        let allConversations = [];
        let isLoadingMore = false;

        // ====== UPDATE loadConversations FUNCTION ======
        async function loadConversations(resetPagination = true) {
            try {
                const archiveFilter = document.getElementById('archive-filter').value;
                let showArchived = 'false';

                if (archiveFilter === 'archived') {
                    showArchived = 'only';
                } else if (archiveFilter === 'all') {
                    showArchived = 'all';
                }

                const response = await fetch(`{{ route("get-multi-compare-chats") }}?show_archived=${showArchived}`);
                allConversations = await response.json();

                if (resetPagination) {
                    currentPage = 1;
                }

                if (allConversations.length === 0) {
                    conversationsList.innerHTML = `
                        <div class="text-center text-gray-500 py-8">
                            <i class="las la-comments text-4xl mb-2"></i>
                            <p>No conversations yet</p>
                        </div>
                    `;
                    return;
                }

                renderConversations();
            } catch (error) {
                console.error('Error loading conversations:', error);
            }
        }

        // ====== NEW: Render conversations with pagination ======
        function renderConversations() {
            const startIndex = 0;
            const endIndex = currentPage * itemsPerPage;
            const conversationsToShow = allConversations.slice(startIndex, endIndex);
            const hasMore = endIndex < allConversations.length;

            conversationsList.innerHTML = conversationsToShow.map(conv => {
                    const mode = conv.optimization_mode || 'fixed';
                    const modeIcons = {
                        'fixed': '🎯',
                        'smart_same': '🔄',
                        'smart_all': '✨'
                    };
                    const modeIcon = modeIcons[mode] || '🎯';

                    const modeTooltips = {
                        'fixed': 'Manual Select',
                        'smart_same': 'Auto-Match',
                        'smart_all': 'Auto-Best'
                    };
                    const modeTooltip = modeTooltips[mode] || 'Manual Select';

                    const modeColors = {
                        'fixed': 'bg-gray-100 text-gray-700',
                        'smart_same': 'bg-blue-100 text-blue-700',
                        'smart_all': 'bg-purple-100 text-purple-700'
                    };
                    const modeColor = modeColors[mode] || 'bg-gray-100 text-gray-700';

                    const isArchived = conv.archived || false;

                    return `
                        <div class="conversation-item bg-white border border-gray-200 hover:border-indigo-300 hover:shadow p-2.5 rounded-lg cursor-pointer transition-all mb-1.5 ${isArchived ? 'opacity-75' : ''}"
                            data-hex-code="${conv.hex_code}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="conversation-title font-semibold text-gray-900 truncate flex-1 text-sm" title="${escapeHtml(conv.title)}">
                                            ${escapeHtml(conv.title)}
                                        </h3>
                                        <span class="text-xs ${modeColor} px-1.5 py-0.5 rounded" title="${modeTooltip}">
                                            ${modeIcon}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                        <i class="las la-clock"></i>
                                        <span>${new Date(conv.updated_at).toLocaleDateString()}</span>
                                        <span class="text-gray-400">•</span>
                                        <div class="flex gap-1">
                                            ${conv.selected_models.map(model => {
                                                const provider = getProviderFromModel(model);
                                                const providerName = provider.charAt(0).toUpperCase() + provider.slice(1);
                                                return `<span class="inline-flex items-center justify-center w-4 h-4 rounded bg-indigo-50 text-xs" title="${providerName}">${getProviderIcon(provider)}</span>`;
                                            }).join('')}
                                        </div>
                                        ${isArchived ? '<span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded ml-auto">Archived</span>' : ''}
                                    </div>
                                </div>
                                <div class="conversation-actions-menu">
                                    <button class="conversation-menu-button text-gray-400 hover:text-gray-700 hover:bg-gray-100 p-1 rounded transition-all"
                                            data-hex-code="${conv.hex_code}"
                                            title="Actions">
                                        <i class="las la-ellipsis-v"></i>
                                    </button>
                                    <div class="conversation-actions-dropdown" data-hex-code="${conv.hex_code}">
                                        <!-- ✅ NEW: Share action -->
                                        <div class="conversation-action-item share-action share-conversation-btn"
                                            data-hex-code="${conv.hex_code}">
                                            <i class="las la-share-alt"></i>
                                            <span>Share</span>
                                        </div>

                                        <!-- ✅ NEW: Export action -->
                                        <div class="conversation-action-item export-action export-conversation-btn"
                                            data-hex-code="${conv.hex_code}">
                                            <i class="las la-download"></i>
                                            <span>Export</span>
                                        </div>

                                        <div class="conversation-action-item archive-action archive-conversation-btn"
                                            data-hex-code="${conv.hex_code}"
                                            data-archived="${isArchived}">
                                            <i class="las ${isArchived ? 'la-box-open' : 'la-archive'}"></i>
                                            <span>${isArchived ? 'Unarchive' : 'Archive'}</span>
                                        </div>
                                        <div class="conversation-action-item edit-action edit-conversation-btn"
                                            data-hex-code="${conv.hex_code}">
                                            <i class="las la-edit"></i>
                                            <span>Edit Title</span>
                                        </div>
                                        <div class="conversation-action-item delete-action delete-conversation-btn"
                                            data-hex-code="${conv.hex_code}">
                                            <i class="las la-trash"></i>
                                            <span>Delete</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

            // Add "Load More" button if there are more conversations
            if (hasMore) {
                conversationsList.innerHTML += `
                    <div class="text-center py-4">
                        <button id="load-more-btn" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all shadow-md hover:shadow-lg flex items-center gap-2 mx-auto text-sm font-semibold">
                            <i class="las la-angle-down text-lg"></i>
                            <span>Load More (${allConversations.length - endIndex} remaining)</span>
                        </button>
                    </div>
                `;
            }

            // Re-enable selection mode if it was active
            if (selectionMode) {
                enableSelectionMode();
            }

            // Add event listener for Load More button
            const loadMoreBtn = document.getElementById('load-more-btn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    if (!isLoadingMore) {
                        isLoadingMore = true;
                        loadMoreBtn.disabled = true;
                        loadMoreBtn.innerHTML = '<i class="las la-spinner la-spin"></i> <span>Loading...</span>';

                        setTimeout(() => {
                            currentPage++;
                            renderConversations();
                            isLoadingMore = false;
                        }, 300); // Small delay for UX
                    }
                });
            }
        }

        async function loadConversation(hexCode) {
            try {
                const newUrl = `{{ route('chat.compare') }}/${hexCode}`;
                window.history.pushState({ hexCode: hexCode }, '', newUrl);
                console.log('✅ URL updated to:', newUrl);
                
                const response = await fetch(`{{ url('/get-multi-compare-conversation') }}/${hexCode}`);
                const conversation = await response.json();

                if (!conversation || !conversation.messages || conversation.messages.length === 0) {
                    console.error('No conversation data or empty conversation');
                    alert('No conversation data available');
                    return;
                }

                currentHexCode = hexCode;
                // ✅ Save hex_code to localStorage when loading a conversation
                localStorage.setItem('multi_compare_current_hex_code', hexCode);
                const savedMode = conversation.optimization_mode || 'fixed';

                console.log('Loading conversation with mode:', savedMode);

                if (savedMode === 'fixed') {
                    // Find all models used
                    const modelsUsed = new Set();
                    conversation.messages.forEach(msg => {
                        if (msg.role === 'assistant' && msg.all_responses) {
                            Object.keys(msg.all_responses).forEach(modelId => modelsUsed.add(modelId));
                        }
                    });

                    // Check boxes for models used
                    document.querySelectorAll('.model-checkbox').forEach(cb => cb.checked = false);
                    modelsUsed.forEach(modelId => {
                        const cb = document.querySelector(`input.model-checkbox[value="${modelId}"]`);
                        if (cb) {
                            cb.checked = true;
                            console.log('✅ Checked model:', modelId);
                        }
                    });
                    
                    // ✅ CRITICAL: Pass skipAutoSelection=true to prevent unchecking
                    setOptimizationMode(savedMode, true, true);  // skipConfirmation=true, skipAutoSelection=true
                    
                    // ✅ CRITICAL: Explicitly call updateSelectedModels after setting mode
                    updateSelectedModels();
                    
                    // ✅ CRITICAL: Wait for DOM to update
                    await new Promise(r => setTimeout(r, 100));
                    
                    console.log('✅ Fixed mode setup complete', {
                        modelsUsed: Array.from(modelsUsed),
                        selectedModels: selectedModels.map(m => m.model)
                    });

                    // Initialize conversation history
                    conversationHistory = {};
                    selectedModels.forEach(modelPanel => {
                        conversationHistory[modelPanel.model] = [];
                    });

                    // Group messages into exchanges
                    const exchanges = [];
                    let currentExchange = null;
                    conversation.messages.forEach(msg => {
                        if (msg.role === 'user') {
                            if (currentExchange) exchanges.push(currentExchange);
                            currentExchange = { userMessage: msg.content, assistantResponses: {}, attachments: msg.attachments || [] };
                        }
                        else if (msg.role === 'assistant' && currentExchange) {
                            currentExchange.assistantResponses = msg.all_responses || {};
                        }
                    });
                    if (currentExchange) exchanges.push(currentExchange);

                    // Display exchanges in each model's panel
                    selectedModels.forEach(panelModel => {
                        const convDiv = document.getElementById(`conversation-${panelModel.model}`);
                        if (!convDiv) return;
                        convDiv.innerHTML = '';

                        exchanges.forEach((exchange, exchangeIndex) => {
                            console.log('DEBUG: Processing exchange', exchangeIndex, exchange.assistantResponses);
                            let resp = exchange.assistantResponses[panelModel.model];
                            
                            // ✅ Fallback to 'export' content if specific model response is missing
                            // This handles the case where export messages are saved under 'export' key
                            if (!resp && exchange.assistantResponses['export']) {
                                resp = exchange.assistantResponses['export'];
                            }
                            
                            if (!resp) return;

                            const entryDiv = document.createElement('div');
                            entryDiv.className = 'conversation-entry';
                            const userMsgId = `user-msg-${panelModel.model}-${exchangeIndex}`;
                            
                            // ✅ User message - use consistent helper
                            const userMessageDiv = createUserMessageHTML(exchange.userMessage, userMsgId, null, exchange.attachments);
                            // Extract the user-prompt and actions from the helper's output
                            const userPromptFromHelper = userMessageDiv.querySelector('.user-prompt');
                            const actionsFromHelper = userMessageDiv.querySelector('.message-actions');
                            entryDiv.appendChild(userPromptFromHelper);
                            entryDiv.appendChild(actionsFromHelper);

                            // Assistant response
                            const assistantMsgId = `assistant-msg-${panelModel.model}-${exchangeIndex}`;
                            const assistantDiv = document.createElement('div');
                            assistantDiv.className = 'assistant-response';
                            assistantDiv.dataset.messageId = assistantMsgId;
                            assistantDiv.dataset.model = panelModel.model;
                            
                            const respDiv = document.createElement('div');
                            respDiv.className = 'message-content';
                            if (isImageURL(resp)) {
                                const imgContainer = document.createElement('div');
                                imgContainer.className = 'my-4';
                                const img = document.createElement('img');
                                img.src = resp;
                                img.alt = 'Generated image';
                                img.className = 'rounded-lg max-w-full h-auto shadow-md cursor-pointer';
                                img.onclick = () => openImageModal(resp, 'Generated image');
                                imgContainer.appendChild(img);
                                respDiv.appendChild(imgContainer);
                            } else {
                                respDiv.setAttribute('data-needs-processing', 'true');
                                respDiv.textContent = resp;
                            }
                            assistantDiv.appendChild(respDiv);
                            assistantDiv.appendChild(createMessageActions(assistantMsgId, true, panelModel.model));
                            
                            // ✅ RENDER PERSISTED FILE BUTTONS
                            // ✅ RENDER PERSISTED FILE BUTTONS (FIXED MODE - FILTER BY MODEL)
                            if (exchange.assistantResponses.files && Array.isArray(exchange.assistantResponses.files)) {
                                // Filter files to only show those belonging to THIS model
                                const modelFiles = exchange.assistantResponses.files.filter(file => file.model === panelModel.model);
                                
                                if (modelFiles.length > 0) {
                                    const filesContainer = document.createElement('div');
                                    modelFiles.forEach(file => {
                                    const fileCard = document.createElement('div');
                                    fileCard.className = 'file-download-card mt-4 p-4 bg-green-50 rounded-lg border border-green-200';
                                    fileCard.innerHTML = `
                                        <div class="flex items-center gap-4">
                                            <div class="p-3 bg-green-100 rounded-lg text-green-600">
                                                <i class="las la-file-${file.file_format === 'PDF' ? 'pdf' : (file.file_format === 'DOCX' ? 'word' : (file.file_format === 'PPTX' ? 'powerpoint' : 'alt'))} text-2xl"></i>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-800">Export Complete</h4>
                                                <p class="text-sm text-gray-600">Generated ${file.file_format} file</p>
                                            </div>
                                            <a href="${file.download_url}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                                                <i class="las la-download"></i> Download
                                            </a>
                                        </div>
                                    `;
                                    filesContainer.appendChild(fileCard);
                                });
                                assistantDiv.appendChild(filesContainer);
                                }
                            }
                            
                            entryDiv.appendChild(assistantDiv);
                            convDiv.appendChild(entryDiv);

                            conversationHistory[panelModel.model].push({ 
                                prompt: exchange.userMessage, 
                                response: resp 
                            });
                        });

                        convDiv.querySelectorAll('[data-needs-processing="true"]').forEach(processMessageContent);
                        convDiv.scrollTop = convDiv.scrollHeight;
                    });

                } else if (savedMode === 'smart_same') {
                    // ========== SMART(SAME) MODE: One panel per provider, multiple models per provider ==========
                    
                    // 1. Find all providers and models used
                    const providerModelsMap = {}; // { provider: Set of models }
                    conversation.messages.forEach(msg => {
                        if (msg.role === 'assistant' && msg.all_responses) {
                            Object.keys(msg.all_responses).forEach(modelId => {
                                const cb = document.querySelector(`input.model-checkbox[value="${modelId}"]`);
                                if (cb) {
                                    const provider = cb.dataset.provider;
                                    if (!providerModelsMap[provider]) {
                                        providerModelsMap[provider] = new Set();
                                    }
                                    providerModelsMap[provider].add(modelId);
                                }
                            });
                        }
                    });

                    console.log('Providers and models used:', providerModelsMap);

                    // Check provider checkboxes
                    const providersUsed = Object.keys(providerModelsMap);
                    document.querySelectorAll('.provider-checkbox').forEach(cb => cb.checked = false);
                    providersUsed.forEach(provider => {
                        const cb = document.querySelector(`input.provider-checkbox[value="${provider}"]`);
                        if (cb) {
                            cb.checked = true;
                            console.log('✅ Checked provider:', provider);
                        }
                    });
                    
                    // ✅ CRITICAL: Pass skipAutoSelection=true
                    setOptimizationMode(savedMode, true, true);  // skipConfirmation=true, skipAutoSelection=true
                    
                    // ✅ CRITICAL: Explicitly update
                    updateSelectedModels();
                    
                    // ✅ CRITICAL: Wait for DOM
                    await new Promise(r => setTimeout(r, 100));

                    // 3. Create ONE panel per provider (using a stable panel ID)
                    selectedModels = providersUsed.map(provider => {
                        const providerCb = document.querySelector(`input.provider-checkbox[value="${provider}"]`);
                        return {
                            model: `${provider}_smart_panel`, // Stable panel ID
                            provider: provider,
                            displayName: `${provider.charAt(0).toUpperCase() + provider.slice(1)} (Smart Mode)`
                        };
                    });

                    generateModelPanels();
                    await new Promise(r => setTimeout(r, 100));

                    // 4. Initialize conversation history for each provider panel
                    conversationHistory = {};
                    selectedModels.forEach(panel => {
                        conversationHistory[panel.model] = [];
                    });

                    // 5. Group messages into exchanges
                    const exchanges = [];
                    let currentExchange = null;
                    conversation.messages.forEach(msg => {
                        if (msg.role === 'user') {
                            if (currentExchange) exchanges.push(currentExchange);
                            currentExchange = { userMessage: msg.content, assistantResponses: {}, attachments: msg.attachments || [] };
                        }
                        else if (msg.role === 'assistant' && currentExchange) {
                            currentExchange.assistantResponses = msg.all_responses || {};
                        }
                    });
                    if (currentExchange) exchanges.push(currentExchange);

                    // 6. Display exchanges in each provider's panel
                    selectedModels.forEach(panelData => {
                        const convDiv = document.getElementById(`conversation-${panelData.model}`);
                        if (!convDiv) {
                            console.error('Panel not found for:', panelData.model);
                            return;
                        }
                        convDiv.innerHTML = '';

                        const provider = panelData.provider;
                        const modelsForProvider = Array.from(providerModelsMap[provider] || []);

                        console.log(`Loading ${provider} panel with models:`, modelsForProvider);

                        exchanges.forEach((exchange, exchangeIndex) => {
                            // Find the response from ANY model in this provider
                            let responseText = null;
                            let modelUsed = null;
                            
                            for (const modelId of modelsForProvider) {
                                if (exchange.assistantResponses[modelId]) {
                                    responseText = exchange.assistantResponses[modelId];
                                    modelUsed = modelId;
                                    break;
                                }
                            }
                            
                            // ✅ Fallback to 'export' if no specific model response found
                            // This handles the case where export messages are saved under 'export' key
                            if (!responseText && exchange.assistantResponses['export']) {
                                responseText = exchange.assistantResponses['export'];
                            }

                            if (!responseText) return; // No response from this provider for this exchange

                            const entryDiv = document.createElement('div');
                            entryDiv.className = 'conversation-entry';
                            const userMsgId = `user-msg-${panelData.model}-${exchangeIndex}`;
                            
                            // ✅ User message - use consistent helper
                            const userMessageDiv = createUserMessageHTML(exchange.userMessage, userMsgId, null, exchange.attachments);
                            // Extract the user-prompt and actions from the helper's output
                            const userPromptFromHelper = userMessageDiv.querySelector('.user-prompt');
                            const actionsFromHelper = userMessageDiv.querySelector('.message-actions');
                            entryDiv.appendChild(userPromptFromHelper);
                            entryDiv.appendChild(actionsFromHelper);

                            // ✅ Assistant response WITHOUT model indicator
                            const assistantMsgId = `assistant-msg-${panelData.model}-${exchangeIndex}`;
                            const assistantDiv = document.createElement('div');
                            assistantDiv.className = 'assistant-response';
                            assistantDiv.dataset.messageId = assistantMsgId;
                            assistantDiv.dataset.model = panelData.model;

                            const respDiv = document.createElement('div');
                            respDiv.className = 'message-content';
                            if (isImageURL(responseText)) {
                                const imgContainer = document.createElement('div');
                                imgContainer.className = 'my-4';
                                const img = document.createElement('img');
                                img.src = responseText;
                                img.alt = 'Generated image';
                                img.className = 'rounded-lg max-w-full h-auto shadow-md cursor-pointer';
                                img.onclick = () => openImageModal(responseText, 'Generated image');
                                imgContainer.appendChild(img);
                                respDiv.appendChild(imgContainer);
                            } else {
                                respDiv.setAttribute('data-needs-processing', 'true');
                                respDiv.textContent = responseText;
                            }

                            assistantDiv.appendChild(respDiv);
                            assistantDiv.appendChild(createMessageActions(assistantMsgId, true, panelData.model));
                            
                            // ✅ RENDER PERSISTED FILE BUTTONS (Smart Same Mode)
                            // ✅ RENDER PERSISTED FILE BUTTONS (SMART SAME MODE - FILTER BY PROVIDER)
                            if (exchange.assistantResponses.files && Array.isArray(exchange.assistantResponses.files)) {
                                // Filter files to only show those belonging to THIS provider
                                // File.model might be like "openai_smart_panel" or specific model from this provider
                                const providerFiles = exchange.assistantResponses.files.filter(file => {
                                    // Check if file's model matches this panel
                                    if (file.model === panelData.model) {
                                        return true;
                                    }
                                    
                                    // Or check if file's model is from this provider
                                    const fileModelCheckbox = document.querySelector(`input.model-checkbox[value="${file.model}"]`);
                                    if (fileModelCheckbox && fileModelCheckbox.dataset.provider === panelData.provider) {
                                        return true;
                                    }
                                    
                                    return false;
                                });
                                
                                if (providerFiles.length > 0) {
                                    const filesContainer = document.createElement('div');
                                    providerFiles.forEach(file => {
                                    const fileCard = document.createElement('div');
                                    fileCard.className = 'file-download-card mt-4 p-4 bg-green-50 rounded-lg border border-green-200';
                                    fileCard.innerHTML = `
                                        <div class="flex items-center gap-4">
                                            <div class="p-3 bg-green-100 rounded-lg text-green-600">
                                                <i class="las la-file-${file.file_format === 'PDF' ? 'pdf' : (file.file_format === 'DOCX' ? 'word' : (file.file_format === 'PPTX' ? 'powerpoint' : 'alt'))} text-2xl"></i>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-800">Export Complete</h4>
                                                <p class="text-sm text-gray-600">Generated ${file.file_format} file</p>
                                            </div>
                                            <a href="${file.download_url}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                                                <i class="las la-download"></i> Download
                                            </a>
                                        </div>
                                    `;
                                    filesContainer.appendChild(fileCard);
                                });
                                assistantDiv.appendChild(filesContainer);
                                }
                            }

                            entryDiv.appendChild(assistantDiv);
                            convDiv.appendChild(entryDiv);

                            conversationHistory[panelData.model].push({ 
                                prompt: exchange.userMessage, 
                                response: responseText // ✅ Clean response without model prefix
                            });
                        });

                        convDiv.querySelectorAll('[data-needs-processing="true"]').forEach(processMessageContent);
                        convDiv.scrollTop = convDiv.scrollHeight;
                    });

                } else if (savedMode === 'smart_all') {
                    // ========== SMART(ALL) MODE: Single panel with best model per message ==========
                    
                    setOptimizationMode('smart_all', true); // ✅ Skip confirmation when loading
                    
                    selectedModels = [{
                        model: 'smart_all_auto',
                        provider: 'smart_all',
                        displayName: 'Smart Mode'
                    }];

                    generateModelPanels();
                    await new Promise(r => setTimeout(r, 100));

                    conversationHistory = { 'smart_all_auto': [] };

                    const convDiv = document.querySelector('[data-model-id="smart_all_auto"] .model-conversation');
                    if (!convDiv) {
                        console.error('Smart Mode conversation container not found');
                        return;
                    }

                    convDiv.innerHTML = '';

                    const exchanges = [];
                    let currentExchange = null;
                    conversation.messages.forEach(msg => {
                        if (msg.role === 'user') {
                            if (currentExchange) exchanges.push(currentExchange);
                            currentExchange = { userMessage: msg.content, assistantResponses: {}, attachments: msg.attachments || [] };
                        }
                        else if (msg.role === 'assistant' && currentExchange) {
                            currentExchange.assistantResponses = msg.all_responses || {};
                        }
                    });
                    if (currentExchange) exchanges.push(currentExchange);

                    exchanges.forEach((exchange, exchangeIndex) => {
                        const entryDiv = document.createElement('div');
                        entryDiv.className = 'conversation-entry';
                        const userMsgId = `user-msg-smart_all_auto-${exchangeIndex}`;

                        // ✅ User message - use consistent helper
                        const userMessageDiv = createUserMessageHTML(exchange.userMessage, userMsgId, null, exchange.attachments);
                        // Extract the user-prompt and actions from the helper's output
                        const userPromptFromHelper = userMessageDiv.querySelector('.user-prompt');
                        const actionsFromHelper = userMessageDiv.querySelector('.message-actions');
                        entryDiv.appendChild(userPromptFromHelper);
                        entryDiv.appendChild(actionsFromHelper);

                        const responses = Object.entries(exchange.assistantResponses);
                        if (responses.length > 0) {
                            // Find the first valid response (prioritizing non-export, or handling export fallback)
                            let modelUsed = null;
                            let responseText = null;

                            // If we have an 'export' key and nothing else, or we just want to render content
                            if (exchange.assistantResponses['export']) {
                                responseText = exchange.assistantResponses['export'];
                                modelUsed = 'export';
                            } else {
                                // Default to first available
                                [modelUsed, responseText] = responses[0];
                            }
                            
                            const assistantMsgId = `assistant-msg-smart_all_auto-${exchangeIndex}`;
                            const assistantDiv = document.createElement('div');
                            assistantDiv.className = 'assistant-response';
                            assistantDiv.dataset.messageId = assistantMsgId;
                            assistantDiv.dataset.model = 'smart_all_auto';

                            const respDiv = document.createElement('div');
                            respDiv.className = 'message-content';
                            if (isImageURL(responseText)) {
                                const imgContainer = document.createElement('div');
                                imgContainer.className = 'my-4';
                                const img = document.createElement('img');
                                img.src = responseText;
                                img.alt = 'Generated image';
                                img.className = 'rounded-lg max-w-full h-auto shadow-md cursor-pointer';
                                img.onclick = () => openImageModal(responseText, 'Generated image');
                                imgContainer.appendChild(img);
                                respDiv.appendChild(imgContainer);
                            } else {
                                respDiv.setAttribute('data-needs-processing', 'true');
                                respDiv.textContent = responseText;
                            }

                            assistantDiv.appendChild(respDiv);
                            assistantDiv.appendChild(createMessageActions(assistantMsgId, true, 'smart_all_auto'));
                            
                            // ✅ RENDER PERSISTED FILE BUTTONS (Smart All Mode)
                            // ✅ RENDER PERSISTED FILE BUTTONS (SMART ALL MODE - FILTER BY SMART_ALL_AUTO)
                            if (exchange.assistantResponses.files && Array.isArray(exchange.assistantResponses.files)) {
                                // Filter files to only show those belonging to smart_all_auto
                                const smartAllFiles = exchange.assistantResponses.files.filter(file => 
                                    file.model === 'smart_all_auto' || !file.model // No model means it's for smart_all
                                );
                                
                                if (smartAllFiles.length > 0) {
                                    const filesContainer = document.createElement('div');
                                    smartAllFiles.forEach(file => {
                                    const fileCard = document.createElement('div');
                                    fileCard.className = 'file-download-card mt-4 p-4 bg-green-50 rounded-lg border border-green-200';
                                    fileCard.innerHTML = `
                                        <div class="flex items-center gap-4">
                                            <div class="p-3 bg-green-100 rounded-lg text-green-600">
                                                <i class="las la-file-${file.file_format === 'PDF' ? 'pdf' : (file.file_format === 'DOCX' ? 'word' : (file.file_format === 'PPTX' ? 'powerpoint' : 'alt'))} text-2xl"></i>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-800">Export Complete</h4>
                                                <p class="text-sm text-gray-600">Generated ${file.file_format} file</p>
                                            </div>
                                            <a href="${file.download_url}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                                                <i class="las la-download"></i> Download
                                            </a>
                                        </div>
                                    `;
                                    filesContainer.appendChild(fileCard);
                                });
                                assistantDiv.appendChild(filesContainer);
                                }
                            }

                            entryDiv.appendChild(assistantDiv);

                            conversationHistory['smart_all_auto'].push({ 
                                prompt: exchange.userMessage, 
                                response: responseText // ✅ Clean response without model prefix
                            });
                        }

                        convDiv.appendChild(entryDiv);
                    });

                    convDiv.querySelectorAll('[data-needs-processing="true"]').forEach(processMessageContent);
                    convDiv.scrollTop = convDiv.scrollHeight;
                }

                const mainContent = document.getElementById('main-content');
                sidebar.classList.add('sidebar-hidden');
                sidebar.classList.remove('sidebar-visible');
                mainContent.classList.add('main-content-normal');
                mainContent.classList.remove('main-content-shifted');

            } catch (err) {
                console.error('Error loading conversation:', err);
                alert('Failed to load conversation');
            }
        }

        // ✅ Helper function to create attachment badge - ULTRA COMPACT WITH IMAGE THUMBNAILS AND DOWNLOADABLE NAMES
        function createAttachmentBadge(attachment) {
            const attachmentBadge = document.createElement('div');
            
            // Check if it's an image type
            const imageTypes = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp'];
            const isImage = imageTypes.includes(attachment.type?.toLowerCase());
            
            if (isImage) {
                // For images - show thumbnail with filename and preview button
                attachmentBadge.className = 'mt-2 flex items-center gap-2';
                attachmentBadge.innerHTML = `
                    <img src="${attachment.url}" 
                        alt="${attachment.name}" 
                        class="w-12 h-12 object-cover rounded cursor-pointer hover:opacity-80 transition border border-white/30"
                        onclick="openImageModal('${attachment.url}', '${attachment.name}')"
                        title="Click to view full size">
                    <a href="${attachment.url}" 
                    download="${attachment.name}"
                    class="text-xs text-dark/70 hover:text-dark flex-1 truncate underline decoration-dotted hover:decoration-solid transition-all" 
                    title="Click to download: ${attachment.name}"
                    onclick="event.stopPropagation()">
                        ${attachment.name}
                    </a>
                    <button class="text-dark/70 hover:text-dark transition-colors flex items-center justify-center p-0 m-0 w-5 h-5" 
                            onclick="openImageModal('${attachment.url}', '${attachment.name}')"
                            title="View image">
                        <i class="las la-eye text-sm"></i>
                    </button>
                `;
            } else {
                // For documents - icon with filename and preview button
                attachmentBadge.className = 'mt-2 flex items-center gap-2 text-xs text-dark/80';
                
                let icon = 'la-paperclip';
                if (attachment.type === 'pdf') icon = 'la-file-pdf';
                else if (['doc', 'docx'].includes(attachment.type)) icon = 'la-file-word';
                
                const displayName = attachment.name.length > 30 
                    ? attachment.name.substring(0, 27) + '...' 
                    : attachment.name;
                
                attachmentBadge.innerHTML = `
                    <i class="las ${icon}"></i>
                    <a href="${attachment.url}" 
                    download="${attachment.name}"
                    class="flex-1 truncate hover:text-dark underline decoration-dotted hover:decoration-solid transition-all" 
                    title="Click to download: ${attachment.name}"
                    onclick="event.stopPropagation()">
                        ${displayName}
                    </a>
                    <button class="text-dark/60 hover:text-dark transition-colors flex items-center justify-center p-0 m-0 w-5 h-5" 
                            onclick="previewAttachmentFromUrl('${attachment.url}', '${attachment.name}', '${attachment.type}')"
                            title="Preview">
                        <i class="las la-eye text-sm"></i>
                    </button>
                `;
            }
            
            return attachmentBadge;
        }

        // Helper function to create message actions
        function createMessageActions(messageId, isAssistant, modelId = null) {
            const actionsDiv = document.createElement('div');
            actionsDiv.className = 'message-actions';
            
            let actionsHTML = `
                <button class="message-action-btn copy-msg-btn" data-message-id="${messageId}" title="Copy ${isAssistant ? 'Response' : 'Message'}">
                    <i class="las la-copy"></i>
                </button>
                <button class="message-action-btn read-msg-btn" data-message-id="${messageId}" title="Read Aloud">
                    <i class="las la-volume-up"></i>
                </button>
            `;
            
            if (isAssistant && modelId) {
                actionsHTML += `
                    <button class="message-action-btn regenerate-msg-btn" data-message-id="${messageId}" data-model="${modelId}" title="Regenerate Response">
                        <i class="las la-redo-alt"></i>
                    </button>
                `;
            }
            
            actionsHTML += `
                <button class="message-action-btn translate-msg-btn" data-message-id="${messageId}" title="Translate ${isAssistant ? 'Response' : 'Message'}">
                    <i class="las la-language"></i>
                </button>
            `;
            
            actionsDiv.innerHTML = actionsHTML;
            return actionsDiv;
        }

        // ✅ NEW: Global function to preview attachments from URL (for loaded conversations)
        window.previewAttachmentFromUrl = async function(url, name, type) {
            try {
                const response = await fetch(url);
                const blob = await response.blob();
                const file = new File([blob], name, { type: `application/${type}` });
                await openAttachmentPreview(file, url);
            } catch (error) {
                console.error('Error previewing attachment:', error);
                alert('Failed to load attachment preview');
            }
        };

        async function deleteConversation(hexCode) {
            try {
                await fetch(`{{ url('/delete-multi-compare-conversation') }}/${hexCode}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    }
                });

                if (currentHexCode == hexCode) {
                    currentHexCode = null;
                    // ✅ Clear localStorage when deleting current conversation
                    localStorage.removeItem('multi_compare_current_hex_code');
                    regenerateModelPanels();
                }
                
                loadConversations();
            } catch (error) {
                console.error('Error deleting conversation:', error);
                alert('Error deleting conversation');
            }
        }

        async function updateConversationTitle(hexCode, newTitle) {
            try {
                await fetch(`{{ url('/update-multi-compare-conversation-title') }}/${hexCode}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ title: newTitle })
                });
                loadConversations();
            } catch (error) {
                console.error('Error updating conversation title:', error);
                alert('Error updating conversation title');
            }
        }

        // ✅ NEW: Show export dialog
        function showExportDialog(hexCode) {
            const exportDialog = document.createElement('div');
            exportDialog.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            exportDialog.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-gray-900">Export Conversation</h3>
                        <button class="close-export-dialog text-gray-400 hover:text-gray-600">
                            <i class="las la-times text-2xl"></i>
                        </button>
                    </div>
                    <p class="text-gray-600 mb-6">Choose a format to export your conversation:</p>
                    <div class="space-y-3">
                        <button class="export-format-btn w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-all"
                                data-format="markdown"
                                data-hex-code="${hexCode}">
                            <div class="flex items-center gap-3">
                                <i class="lab la-markdown text-3xl text-purple-600"></i>
                                <div class="text-left">
                                    <div class="font-medium text-gray-900">Markdown (.md)</div>
                                    <div class="text-sm text-gray-500">Perfect for documentation & notes</div>
                                </div>
                            </div>
                            <i class="las la-arrow-right text-gray-400"></i>
                        </button>
                        <button class="export-format-btn w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-all"
                                data-format="json"
                                data-hex-code="${hexCode}">
                            <div class="flex items-center gap-3">
                                <i class="las la-code text-3xl text-purple-600"></i>
                                <div class="text-left">
                                    <div class="font-medium text-gray-900">JSON (.json)</div>
                                    <div class="text-sm text-gray-500">Complete data with metadata</div>
                                </div>
                            </div>
                            <i class="las la-arrow-right text-gray-400"></i>
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(exportDialog);

            // Close dialog handlers
            exportDialog.querySelector('.close-export-dialog').addEventListener('click', () => {
                exportDialog.remove();
            });

            exportDialog.addEventListener('click', (e) => {
                if (e.target === exportDialog) {
                    exportDialog.remove();
                }
            });

            // Export format button handlers
            exportDialog.querySelectorAll('.export-format-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const format = btn.dataset.format;
                    const hexCode = btn.dataset.hexCode;
                    await exportConversation(hexCode, format);
                    exportDialog.remove();
                });
            });
        }

        // ✅ NEW: Export conversation function
        async function exportConversation(hexCode, format) {
            try {
                console.log('Exporting conversation:', { hexCode, format });

                const baseUrl = '{{ url('') }}';
                const url = `${baseUrl}/export-multi-chat-conversation/${hexCode}`;
                console.log('Base URL:', baseUrl);
                console.log('Full Request URL:', url);

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ format: format })
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', Object.fromEntries(response.headers.entries()));

                if (!response.ok) {
                    const errorData = await response.json().catch(() => null);
                    const errorMessage = errorData?.error || errorData?.message || `Export failed (${response.status})`;
                    console.error('Export error:', errorData);
                    throw new Error(errorMessage);
                }

                // Get filename from Content-Disposition header or use default
                const contentDisposition = response.headers.get('Content-Disposition');
                let filename = `multi-chat-${hexCode}.${format === 'markdown' ? 'md' : 'json'}`;
                if (contentDisposition) {
                    const matches = /filename="([^"]+)"/.exec(contentDisposition);
                    if (matches && matches[1]) {
                        filename = matches[1];
                    }
                }

                // Get the blob
                const blob = await response.blob();

                // Create download link
                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(downloadUrl);
                document.body.removeChild(a);

                // Show success message
                showNotification(`Conversation exported successfully as ${format.toUpperCase()}!`, 'success');
            } catch (error) {
                console.error('Error exporting conversation:', error);
                showNotification(error.message || 'Failed to export conversation', 'error');
            }
        }

        // Helper function for notifications
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                'bg-blue-500'
            } transition-opacity duration-300`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Utility functions
        function addChatGPTLinkStyles(element) {
            const links = element.querySelectorAll('a');
            links.forEach(link => {
                link.classList.add('text-blue-600', 'hover:text-blue-800', 'hover:underline', 'transition-colors', 'duration-200');
                
                if (link.href && !link.href.startsWith(window.location.origin)) {
                    const icon = document.createElement('span');
                    icon.innerHTML = '&nbsp;↗';
                    icon.classList.add('inline-block', 'text-xs', 'align-super');
                    link.appendChild(icon);
                }
            });

            element.querySelectorAll('ul').forEach(ul => ul.classList.add('list-disc', 'pl-6', 'my-2', 'space-y-1'));
            element.querySelectorAll('ol').forEach(ol => ol.classList.add('list-decimal', 'pl-6', 'my-2', 'space-y-1'));
            element.querySelectorAll('li').forEach(li => li.classList.add('mb-1'));
            element.querySelectorAll('pre').forEach(pre => pre.classList.add('bg-gray-100', 'p-3', 'rounded', 'overflow-x-auto', 'my-2'));
            element.querySelectorAll('code:not(pre code)').forEach(code => code.classList.add('bg-gray-100', 'px-1', 'py-0.5', 'rounded', 'text-sm'));
            element.querySelectorAll('blockquote').forEach(blockquote => blockquote.classList.add('border-l-4', 'border-gray-300', 'pl-4', 'my-2', 'text-gray-600'));
        }

        function addCopyButtonsToCodeBlocks(container) {
            container.querySelectorAll('pre').forEach((preElement) => {
                if (preElement.querySelector('.copy-code-button')) return;
                
                const containerDiv = document.createElement('div');
                containerDiv.className = 'code-block-container relative';
                
                preElement.parentNode.insertBefore(containerDiv, preElement);
                containerDiv.appendChild(preElement);
                
                const copyButton = document.createElement('button');
                copyButton.className = 'copy-code-button';
                copyButton.textContent = 'Copy';
                copyButton.title = 'Copy to clipboard';
                containerDiv.appendChild(copyButton);
                
                const code = preElement.querySelector('code')?.innerText || preElement.innerText;
                
                copyButton.addEventListener('click', () => {
                    navigator.clipboard.writeText(code).then(() => {
                        copyButton.textContent = 'Copied!';
                        copyButton.classList.add('copied');
                        setTimeout(() => {
                            copyButton.textContent = 'Copy';
                            copyButton.classList.remove('copied');
                        }, 2000);
                    });
                });
            });
        }

        /**
         * Wrap tables in scroll container to prevent overlap in multi-panel layouts
         */
        function wrapTablesInScrollContainer() {
            document.querySelectorAll('.message-content table').forEach(table => {
                // Skip if already wrapped
                if (table.parentElement?.classList.contains('table-wrapper')) {
                    return;
                }
                
                // Create wrapper div
                const wrapper = document.createElement('div');
                wrapper.className = 'table-wrapper';
                
                // Insert wrapper before table and move table into it
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            });
        }

        /**
         * Add export buttons to all tables in the conversation
         */
        function addTableExportButtons() {
            // Find all tables that don't have export buttons yet
            document.querySelectorAll('.message-content table').forEach(table => {
                // Get the wrapper if table is wrapped, otherwise use table
                const wrapper = table.parentElement?.classList.contains('table-wrapper') 
                    ? table.parentElement 
                    : table;
                
                // Skip if already has export button
                if (wrapper.previousElementSibling?.classList.contains('table-export-buttons')) {
                    return;
                }
                
                // Create export buttons container
                const exportButtons = document.createElement('div');
                exportButtons.className = 'table-export-buttons flex items-center gap-2 mb-2 p-2 bg-purple-50 border border-purple-200 rounded-lg';
                exportButtons.innerHTML = `
                    <span class="text-sm font-semibold text-purple-900 flex items-center gap-2">
                        <i class="las la-table text-lg"></i>
                        Export Table:
                    </span>
                    <button class="export-table-btn px-3 py-1.5 text-xs bg-green-600 text-white rounded hover:bg-green-700 transition-colors flex items-center gap-1" data-format="csv">
                        <i class="las la-file-csv"></i>
                        CSV
                    </button>
                    <button class="export-table-btn px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors flex items-center gap-1" data-format="xlsx">
                        <i class="las la-file-excel"></i>
                        Excel
                    </button>
                    <button class="export-table-btn px-3 py-1.5 text-xs bg-red-600 text-white rounded hover:bg-red-700 transition-colors flex items-center gap-1" data-format="pdf">
                        <i class="las la-file-pdf"></i>
                        PDF
                    </button>
                    <button class="export-table-btn px-3 py-1.5 text-xs bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors flex items-center gap-1" data-format="docx">
                        <i class="las la-file-word"></i>
                        Word
                    </button>
                `;
                
                // Insert before wrapper (or table if not wrapped)
                wrapper.parentNode.insertBefore(exportButtons, wrapper);
                
                // Attach event listeners
                exportButtons.querySelectorAll('.export-table-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const format = btn.dataset.format;
                        exportTableToFile(table, format);
                    });
                });
            });
        }

        /**
         * Export table to file
         */
        async function exportTableToFile(table, format) {
            try {
                // Extract table data
                const headers = [];
                const data = [];
                
                // Get headers
                const headerCells = table.querySelectorAll('thead th, thead td');
                headerCells.forEach(cell => {
                    headers.push(cell.textContent.trim());
                });
                
                // Get data rows
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const rowData = {};
                    const cells = row.querySelectorAll('td, th');
                    cells.forEach((cell, index) => {
                        const header = headers[index] || `Column ${index + 1}`;
                        rowData[header] = cell.textContent.trim();
                    });
                    data.push(rowData);
                });
                
                console.log('Exporting table', { format, headers, rows: data.length });
                
                // Show loading notification
                showNotification(`Generating ${format.toUpperCase()} file...`, 'info');
                
                // Generate file based on format
                if (format === 'csv') {
                    await exportTableAsCSV(headers, data);
                } else if (format === 'xlsx') {
                    await exportTableAsExcel(headers, data);
                } else if (format === 'pdf') {
                    await exportTableAsPDF(headers, data);
                } else if (format === 'docx') {
                    await exportTableAsWord(headers, data);
                }
                
            } catch (error) {
                console.error('Export error:', error);
                showNotification('Failed to export table', 'error');
            }
        }

        /**
         * Export table as CSV
         */
        async function exportTableAsCSV(headers, data) {
            // Create CSV content
            let csvContent = 'ChatterMate AI Export\n';
            csvContent += 'Generated: ' + new Date().toLocaleString() + '\n\n';
            
            // Add headers
            csvContent += headers.map(h => `"${h}"`).join(',') + '\n';
            
            // Add data
            data.forEach(row => {
                const values = headers.map(header => {
                    const value = row[header] || '';
                    return `"${value.replace(/"/g, '""')}"`;
                });
                csvContent += values.join(',') + '\n';
            });
            
            // Create blob and upload
            const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const formData = new FormData();
            formData.append('csv_file', blob, 'table_export.csv');
            
            const response = await fetch('{{ route("upload-table-csv") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                showDownloadModal(result.download_url, result.file_name, 'CSV');
            } else {
                throw new Error(result.error || 'Export failed');
            }
        }

        /**
         * Export table as Excel (using SheetJS)
         */
        async function exportTableAsExcel(headers, data) {
            // This requires SheetJS library - for now, send to backend
            await exportTableViaBackend('xlsx', headers, data);
        }

        /**
         * Export table as PDF
         */
        async function exportTableAsPDF(headers, data) {
            await exportTableViaBackend('pdf', headers, data);
        }

        /**
         * Export table as Word
         */
        async function exportTableAsWord(headers, data) {
            await exportTableViaBackend('docx', headers, data);
        }

        /**
         * Export table via backend (for formats that need server processing)
         */
        async function exportTableViaBackend(format, headers, data) {
            const response = await fetch('{{ route("export-table-inline") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    format: format,
                    headers: headers,
                    data: data
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showDownloadModal(result.download_url, result.file_name, format.toUpperCase());
            } else {
                throw new Error(result.error || 'Export failed');
            }
        }

        /**
         * Show download modal
         */
        function showDownloadModal(downloadUrl, fileName, formatName) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-2xl p-8 max-w-md mx-4 animate-bounce-in">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-500 rounded-full mx-auto mb-4 flex items-center justify-center">
                            <i class="las la-check text-white text-4xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Export Ready!</h3>
                        <p class="text-gray-600 mb-6">Your ${formatName} file is ready to download</p>
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <p class="text-sm font-semibold text-gray-700 truncate">${fileName}</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="${downloadUrl}" download="${fileName}" 
                            class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors font-semibold flex items-center justify-center gap-2">
                                <i class="las la-download"></i>
                                Download
                            </a>
                            <button onclick="this.closest('.fixed').remove()" 
                                    class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Auto-remove after download
            modal.querySelector('a').addEventListener('click', () => {
                setTimeout(() => modal.remove(), 2000);
            });
            
            // Close on backdrop click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
            
            showNotification('✅ File ready for download!', 'success');
        }

        function processMessageContent(element) {
            let content = element.textContent || element.innerHTML;
            
            debugLog('Processing message content', {
                contentLength: content.length,
                contentPreview: content.substring(0, 100) + '...'
            });
            
            const chartRegex = /```chart\n([\s\S]*?)\n```/g;
            const chartMatches = [...content.matchAll(chartRegex)];
            
            debugLog('Chart detection', {
                chartBlocksFound: chartMatches.length
            });
            
            if (chartMatches.length > 0) {
                try {
                    let textContent = content;
                    chartMatches.forEach(match => {
                        textContent = textContent.replace(match[0], '');
                    });
                    textContent = textContent.trim();
                    
                    element.innerHTML = '';
                    
                    if (textContent) {
                        const textDiv = document.createElement('div');
                        textDiv.innerHTML = marked.parse(textContent, {
                            gfm: true,
                            breaks: true,
                            headerIds: false,
                            mangle: false
                        });
                        element.appendChild(textDiv);
                    }
                    
                    chartMatches.forEach((match, index) => {
                        try {
                            debugLog(`Processing chart ${index + 1}`);
                            
                            const chartData = JSON.parse(match[1]);
                            
                            debugLog(`Chart data parsed successfully for chart ${index + 1}`, chartData);
                            
                            const chartContainer = document.createElement('div');
                            chartContainer.className = 'chart-container';
                            
                            const canvas = document.createElement('canvas');
                            canvas.className = 'chart-canvas';
                            canvas.id = `chart-${Date.now()}-${Math.random().toString(36).substr(2, 9)}-${index}`;
                            
                            chartContainer.appendChild(canvas);
                            element.appendChild(chartContainer);
                            
                            debugLog(`Created chart container and canvas: ${canvas.id}`);
                            
                            setTimeout(() => {
                                renderChart(canvas, chartData);
                            }, 200);
                            
                        } catch (e) {
                            debugLog(`Error processing chart ${index + 1}`, {
                                error: e.message,
                                stack: e.stack
                            });
                            
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'debug-info';
                            errorDiv.innerHTML = `<strong>Chart Error:</strong> ${e.message}<br><pre style="white-space: pre-wrap;">${match[1]}</pre>`;
                            element.appendChild(errorDiv);
                        }
                    });
                    
                } catch (e) {
                    debugLog('Error in chart processing', {
                        error: e.message,
                        stack: e.stack
                    });
                    
                    element.innerHTML = marked.parse(content, {
                        gfm: true,
                        breaks: true,
                        headerIds: false,
                        mangle: false
                    });
                }
            } else {
                element.innerHTML = marked.parse(content, {
                    gfm: true,
                    breaks: true,
                    headerIds: false,
                    mangle: false
                });
            }

            addChatGPTLinkStyles(element);
            addCopyButtonsToCodeBlocks(element);
            
            // ✅ NEW: Wrap tables in scroll container to prevent overlap
            setTimeout(() => {
                wrapTablesInScrollContainer();
            }, 50);

            // ✅ NEW: Add export buttons to tables
            setTimeout(() => {
                addTableExportButtons();
            }, 100);
            
            if (window.MathJax && window.MathJax.typesetPromise) {
                window.MathJax.typesetPromise([element]).catch((err) => {
                    console.error('MathJax rendering error:', err);
                });
            }
        }

        // ✅ Also add export buttons when loading conversations
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                addTableExportButtons();
            }, 500);
        });

        function renderChart(canvas, chartData) {
            debugLog(`Starting chart render for canvas: ${canvas.id}`, {
                canvasExists: !!canvas,
                canvasId: canvas.id,
                chartData: chartData,
                chartType: chartData.type
            });
            
            if (!Chart) {
                debugLog('Chart.js not available!');
                return;
            }
            
            try {
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    debugLog('Could not get canvas context');
                    return;
                }
                
                const existingChart = Chart.getChart(canvas);
                if (existingChart) {
                    debugLog('Destroying existing chart');
                    existingChart.destroy();
                }
                
                const config = {
                    type: chartData.type || 'bar',
                    data: chartData.data || {
                        labels: chartData.labels || [],
                        datasets: chartData.datasets || []
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: !!chartData.title,
                                text: chartData.title || ''
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                            }
                        },
                        scales: chartData.type !== 'pie' && chartData.type !== 'doughnut' ? {
                            x: {
                                display: true,
                                title: {
                                    display: !!chartData.xLabel,
                                    text: chartData.xLabel || ''
                                }
                            },
                            y: {
                                display: true,
                                title: {
                                    display: !!chartData.yLabel,
                                    text: chartData.yLabel || ''
                                }
                            }
                        } : {}
                    }
                };
                
                if (chartData.options) {
                    config.options = { ...config.options, ...chartData.options };
                }
                
                debugLog(`Creating chart with config`, config);
                
                const chart = new Chart(ctx, config);
                
                debugLog(`Chart created successfully!`, {
                    chartId: chart.id,
                    canvasId: canvas.id,
                    chartType: chart.config.type
                });
                
                return chart;
                
            } catch (error) {
                debugLog('Chart creation failed!', {
                    error: error.message,
                    stack: error.stack,
                    canvasId: canvas.id,
                    chartData: chartData
                });
                
                const container = canvas.parentElement;
                if (container) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'debug-info';
                    errorDiv.innerHTML = `<strong>Chart Render Error:</strong> ${error.message}`;
                    container.appendChild(errorDiv);
                }
            }
        }

        function updateUserStats() {
            fetch('/user/stats')
                .then(res => res.json())
                .then(data => {
                    if (data.credits_left !== undefined && data.tokens_left !== undefined) {
                        const creditsElem = document.getElementById('credits_left');
                        const tokensElem = document.getElementById('tokens_left');
                        const tokensTooltip = document.getElementById('tokens_tooltip');

                        // Update mobile stats
                        const creditsElemMobile = document.getElementById('credits_left_mobile');
                        const tokensElemMobile = document.getElementById('tokens_left_mobile');

                        if (creditsElem) creditsElem.textContent = data.credits_left;
                        if (tokensElem) tokensElem.textContent = data.tokens_left;
                        if (creditsElemMobile) creditsElemMobile.textContent = data.credits_left;
                        if (tokensElemMobile) tokensElemMobile.textContent = data.tokens_left;
                        
                        // ✅ FIXED: Check if tokens just dropped below threshold
                        const previousLowTokenStatus = hasLowTokens;
                        const newTokensLeft = parseInt(data.tokens_left);
                        const nowHasLowTokens = newTokensLeft < 5000;
                        
                        if (nowHasLowTokens && !previousLowTokenStatus) {
                            console.warn('⚠️ Token threshold crossed during stats update!', {
                                tokens: newTokensLeft,
                                current_conversation: currentConversationId
                            });
                            
                            hasLowTokens = true;
                            showNotification(`⚠️ Low tokens (${newTokensLeft.toLocaleString()}). New conversations will use default model only.`, 'warning');
                            
                            // ✅ Only update dropdown, don't disrupt current conversation
                            enforceTokenRestrictionsInDropdown();
                            
                            // Update icon and tooltip styling
                            const tokenIcon = tokensElem.closest('.relative').querySelector('svg');
                            if (tokenIcon) {
                                tokenIcon.classList.add('text-yellow-300');
                            }
                            if (tokensTooltip) {
                                tokensTooltip.classList.add('bg-yellow-600');
                                tokensTooltip.classList.remove('bg-gray-900');
                                const arrow = tokensTooltip.querySelector('.border-b-gray-900');
                                if (arrow) {
                                    arrow.classList.add('border-b-yellow-600');
                                    arrow.classList.remove('border-b-gray-900');
                                }
                            }
                        }
                    }
                })
                .catch(err => console.error('Error fetching updated stats:', err));
        }

        // ✅ NEW: Enforce restrictions in dropdown ONLY (doesn't regenerate panels)
        function enforceTokenRestrictionsInDropdown() {
            if (!hasLowTokens || !defaultModel) {
                return; // No restrictions
            }
            
            console.log('🔒 Applying token restrictions to dropdown (preserving active conversation)', {
                defaultModel: defaultModel,
                currentMode: currentOptimizationMode
            });
            
            if (currentOptimizationMode === 'fixed') {
                // ✅ FIXED MODE: Disable all models except default in dropdown
                document.querySelectorAll('.dropdown-model').forEach(modelDiv => {
                    const checkbox = modelDiv.querySelector('.model-checkbox');
                    if (!checkbox) return;
                    
                    const modelId = checkbox.value;
                    const isDefault = modelId === defaultModel;
                    
                    if (!isDefault) {
                        // Disable non-default models
                        checkbox.disabled = true;
                        modelDiv.style.opacity = '0.4';
                        modelDiv.style.cursor = 'not-allowed';
                        modelDiv.title = 'Insufficient tokens - only default model available';
                        
                        // Prevent clicking
                        modelDiv.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            showNotification('⚠️ Low tokens: Only default model available', 'warning');
                        }, true);
                    } else {
                        // Highlight default model
                        checkbox.disabled = false;
                        modelDiv.style.opacity = '1';
                        modelDiv.style.cursor = 'pointer';
                        modelDiv.classList.add('bg-yellow-50', 'border-2', 'border-yellow-400');
                        modelDiv.title = 'Default model (required due to low tokens)';
                    }
                });
                
                // Also disable provider headers for non-default providers
                document.querySelectorAll('.dropdown-provider').forEach(providerDiv => {
                    const provider = providerDiv.dataset.provider;
                    if (provider !== defaultModelProvider) {
                        providerDiv.style.opacity = '0.4';
                    }
                });
                
            } else if (currentOptimizationMode === 'smart_same') {
                // ✅ SMART (SAME) MODE: Disable all providers except default model's provider
                document.querySelectorAll('.dropdown-model').forEach(modelDiv => {
                    const checkbox = modelDiv.querySelector('.provider-checkbox');
                    if (!checkbox) return;
                    
                    const provider = checkbox.value;
                    const isDefaultProvider = provider === defaultModelProvider;
                    
                    if (!isDefaultProvider) {
                        // Disable non-default providers
                        checkbox.disabled = true;
                        modelDiv.style.opacity = '0.4';
                        modelDiv.style.cursor = 'not-allowed';
                        modelDiv.title = 'Insufficient tokens - only default provider available';
                        
                        // Prevent clicking
                        modelDiv.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            showNotification('⚠️ Low tokens: Only default provider available', 'warning');
                        }, true);
                    } else {
                        // Highlight default provider
                        checkbox.disabled = false;
                        modelDiv.style.opacity = '1';
                        modelDiv.style.cursor = 'pointer';
                        modelDiv.classList.add('bg-yellow-50', 'border-2', 'border-yellow-400');
                        modelDiv.title = 'Default provider (required due to low tokens)';
                    }
                });
            }
            // For smart_all mode, dropdown is already disabled, so no additional restrictions needed
            
            console.log('✅ Dropdown restrictions applied, conversation preserved');
        }

        // Notification helper function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                type === 'warning' ? 'bg-yellow-500' : 
                'bg-blue-500'
            } text-white`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Fade in
            setTimeout(() => {
                notification.style.opacity = '1';
            }, 10);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        // ✅ ENHANCED: Conversation Search Functionality with Backend Search
        const conversationSearchInput = document.getElementById('conversation-search');
        const clearSearchBtn = document.getElementById('clear-search');
        const noSearchResults = document.getElementById('no-search-results');
        let searchTimeout = null;

        // Helper function to escape regex special characters
        function escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        // Search conversations with debouncing
        conversationSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            // Show/hide clear button
            if (searchTerm) {
                clearSearchBtn.classList.remove('hidden');
            } else {
                clearSearchBtn.classList.add('hidden');
            }
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Debounce search (wait 300ms after user stops typing)
            searchTimeout = setTimeout(() => {
                searchConversations(searchTerm);
            }, 300);
        });

        // Clear search
        clearSearchBtn.addEventListener('click', function() {
            conversationSearchInput.value = '';
            clearSearchBtn.classList.add('hidden');
            searchConversations('');
        });

        // Search conversations (backend + frontend)
        async function searchConversations(searchTerm) {
            try {
                // Show loading state
                conversationsList.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                        <p class="mt-2">Searching...</p>
                    </div>
                `;
                
                const response = await fetch('{{ route("search-multi-compare-conversations") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ search: searchTerm })
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Search failed:', response.status, errorText);
                    throw new Error(`Search failed: ${response.status}`);
                }
                
                allConversations = await response.json();
                console.log('Search results:', allConversations.length, 'conversations found');

                // Reset pagination for search results
                currentPage = 1;

                // Display results
                displaySearchResults(searchTerm);
                
            } catch (error) {
                console.error('Search error:', error);
                conversationsList.innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <i class="las la-exclamation-circle text-4xl mb-2"></i>
                        <p>Search failed. Please try again.</p>
                        <p class="text-xs text-gray-600 mt-2">${error.message}</p>
                    </div>
                `;
            }
        }

        // Display search results
        function displaySearchResults(searchTerm) {
            console.log('Displaying results:', allConversations.length, 'conversations');

            if (allConversations.length === 0) {
                noSearchResults.classList.remove('hidden');
                conversationsList.classList.add('hidden');
                conversationsList.innerHTML = '';
                return;
            }

            noSearchResults.classList.add('hidden');
            conversationsList.classList.remove('hidden');

            // Pagination for search results
            const startIndex = 0;
            const endIndex = currentPage * itemsPerPage;
            const conversationsToShow = allConversations.slice(startIndex, endIndex);
            const hasMore = endIndex < allConversations.length;

            conversationsList.innerHTML = conversationsToShow.map(conv => {
                // Get mode display name
                const mode = conv.optimization_mode || 'fixed';
                const modeIcons = {
                    'fixed': '🎯',
                    'smart_same': '🔄',
                    'smart_all': '✨'
                };
                const modeIcon = modeIcons[mode] || '🎯';

                const modeTooltips = {
                    'fixed': 'Manual Select',
                    'smart_same': 'Auto-Match',
                    'smart_all': 'Auto-Best'
                };
                const modeTooltip = modeTooltips[mode] || 'Manual Select';

                // Mode color styling
                const modeColors = {
                    'fixed': 'bg-gray-100 text-gray-700',
                    'smart_same': 'bg-blue-100 text-blue-700',
                    'smart_all': 'bg-purple-100 text-purple-700'
                };
                const modeColor = modeColors[mode] || 'bg-gray-100 text-gray-700';
                
                // Highlight search term in title if present
                let displayTitle = escapeHtml(conv.title);
                if (searchTerm) {
                    try {
                        // ✅ FIXED: Properly escape regex special characters
                        const escapedSearchTerm = escapeRegex(escapeHtml(searchTerm));
                        const regex = new RegExp(`(${escapedSearchTerm})`, 'gi');
                        displayTitle = displayTitle.replace(regex, '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
                    } catch (e) {
                        console.error('Error highlighting search term:', e);
                        // If regex fails, just use the title as-is
                    }
                }
                
                const isArchived = conv.archived || false;

                return `
                    <div class="conversation-item bg-gray-50 hover:bg-gray-100 p-3 rounded-lg cursor-pointer transition-colors"
                        data-hex-code="${conv.hex_code}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="conversation-title font-medium text-gray-900 truncate flex-1">${displayTitle}</h3>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-gray-400 text-sm" title="${new Date(conv.updated_at).toLocaleString()}" style="cursor: help;">
                                            🕒
                                        </span>
                                        <span class="text-sm ${modeColor} px-1.5 py-0.5 rounded flex items-center justify-center" title="${modeTooltip}" style="min-width: 28px;">
                                            ${modeIcon}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    ${conv.selected_models.map(model => {
                                        const provider = getProviderFromModel(model);
                                        const providerName = provider.charAt(0).toUpperCase() + provider.slice(1);
                                        return `<span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded bg-purple-100" title="${providerName}">${getProviderIcon(provider)}</span>`;
                                    }).join('')}
                                </div>
                                ${searchTerm ? `
                                    <div class="mt-2 text-xs text-gray-600 italic flex items-center gap-1">
                                        <i class="las la-search"></i>
                                        <span>Match found in conversation</span>
                                    </div>
                                ` : ''}
                            </div>
                            <div class="conversation-actions-menu ml-2">
                                <button class="conversation-menu-button text-gray-400 hover:text-gray-600" 
                                        data-hex-code="${conv.hex_code}"
                                        title="Actions">
                                    <i class="las la-ellipsis-v text-lg"></i>
                                </button>
                                <div class="conversation-actions-dropdown" data-hex-code="${conv.hex_code}">
                                     <!-- ✅ NEW: Share action -->
                                    <div class="conversation-action-item share-action share-conversation-btn"
                                        data-hex-code="${conv.hex_code}">
                                        <i class="las la-share-alt"></i>
                                        <span>Share</span>
                                    </div>

                                    <!-- ✅ NEW: Export action -->
                                    <div class="conversation-action-item export-action export-conversation-btn"
                                        data-hex-code="${conv.hex_code}">
                                        <i class="las la-download"></i>
                                        <span>Export</span>
                                    </div>

                                    <div class="conversation-action-item archive-action archive-conversation-btn"
                                        data-hex-code="${conv.hex_code}"
                                        data-archived="${isArchived}">
                                        <i class="las ${isArchived ? 'la-box-open' : 'la-archive'}"></i>
                                        <span>${isArchived ? 'Unarchive' : 'Archive'}</span>
                                    </div>
                                    <div class="conversation-action-item edit-action edit-conversation-btn"
                                        data-hex-code="${conv.hex_code}">
                                        <i class="las la-edit"></i>
                                        <span>Edit Title</span>
                                    </div>
                                    <div class="conversation-action-item delete-action delete-conversation-btn"
                                        data-hex-code="${conv.hex_code}">
                                        <i class="las la-trash"></i>
                                        <span>Delete</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // Add "Load More" button for search results if there are more
            if (hasMore) {
                conversationsList.innerHTML += `
                    <div class="text-center py-4">
                        <button id="load-more-search-btn" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors flex items-center gap-2 mx-auto text-sm font-medium">
                            <i class="las la-angle-down"></i>
                            <span>Load More (${allConversations.length - endIndex} more)</span>
                        </button>
                    </div>
                `;
            }

            // Add event listener for Load More button in search
            const loadMoreSearchBtn = document.getElementById('load-more-search-btn');
            if (loadMoreSearchBtn) {
                loadMoreSearchBtn.addEventListener('click', function() {
                    if (!isLoadingMore) {
                        isLoadingMore = true;
                        loadMoreSearchBtn.disabled = true;
                        loadMoreSearchBtn.innerHTML = '<i class="las la-spinner la-spin"></i> <span>Loading...</span>';

                        setTimeout(() => {
                            currentPage++;
                            displaySearchResults(searchTerm);
                            isLoadingMore = false;
                        }, 300);
                    }
                });
            }
        }

        // ✅ Dashboard Navigation
        const gotoDashboardBtn = document.getElementById('goto-dashboard');
        gotoDashboardBtn.addEventListener('click', () => {
            window.location.href = '{{ route("dashboard") }}';
        });

    </script>

    <script>
        // ====== THREE-DOT MENU FUNCTIONALITY ======
        document.addEventListener('click', function(e) {
            // Toggle dropdown when three-dot button is clicked
            if (e.target.closest('.conversation-menu-button')) {
                e.stopPropagation();
                const button = e.target.closest('.conversation-menu-button');
                const dropdown = button.nextElementSibling;
                
                // Close all other dropdowns
                document.querySelectorAll('.conversation-actions-dropdown.show').forEach(d => {
                    if (d !== dropdown) {
                        d.classList.remove('show');
                    }
                });
                
                // Toggle current dropdown
                dropdown.classList.toggle('show');
                return;
            }
            
            // Close dropdown when clicking outside
            if (!e.target.closest('.conversation-actions-dropdown')) {
                document.querySelectorAll('.conversation-actions-dropdown.show').forEach(d => {
                    d.classList.remove('show');
                });
            }
            
            // Handle action clicks within dropdown
            if (e.target.closest('.conversation-action-item')) {
                const dropdown = e.target.closest('.conversation-actions-dropdown');
                if (dropdown) {
                    dropdown.classList.remove('show');
                }
            }
        });

        // Close dropdown when conversation is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const originalLoadConversation = loadConversation;
            loadConversation = function(conversationId) {
                document.querySelectorAll('.conversation-actions-dropdown.show').forEach(d => {
                    d.classList.remove('show');
                });
                return originalLoadConversation(conversationId);
            };
        });
    </script>

    <script>
        // ====== SHARE CONVERSATION FUNCTIONALITY ======
        let currentShareConversationId = null;

        // Share button click handler
        document.addEventListener('click', (e) => {
            if (e.target.closest('.share-conversation-btn')) {
                e.stopPropagation();
                const btn = e.target.closest('.share-conversation-btn');
                const conversationId = btn.dataset.hexCode;
                openShareModal(conversationId);
                return;
            }
        });

        // Open share modal
        async function openShareModal(conversationId) {
            currentShareConversationId = conversationId;
            const modal = document.getElementById('share-modal');
            const loading = document.getElementById('share-loading');
            const content = document.getElementById('share-content');
            
            modal.classList.remove('hidden');
            loading.classList.remove('hidden');
            content.classList.add('hidden');
            
            try {
                // Check if conversation already has a share link
                const response = await fetch(`/multi-compare-conversation/${conversationId}/share-info`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    }
                });
                
                const data = await response.json();
                
                if (data.shared) {
                    // Already shared, display existing link
                    displayShareInfo(data);
                } else {
                    // Generate new share link
                    await generateShareLink(conversationId);
                }
                
            } catch (error) {
                console.error('Error loading share info:', error);
                showNotification('Failed to load share information', 'error');
                modal.classList.add('hidden');
            }
        }

        // Generate share link
        async function generateShareLink(conversationId, expiresInDays = null) {
            const loading = document.getElementById('share-loading');
            const content = document.getElementById('share-content');
            
            loading.classList.remove('hidden');
            content.classList.add('hidden');
            
            try {
                const payload = {};
                if (expiresInDays) {
                    payload.expires_in_days = expiresInDays;
                }
                
                const response = await fetch(`/multi-compare-conversation/${conversationId}/share`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify(payload)
                });
                
                if (!response.ok) {
                    throw new Error('Failed to generate share link');
                }
                
                const data = await response.json();
                displayShareInfo(data);
                showNotification('✓ Share link generated successfully', 'success');
                
            } catch (error) {
                console.error('Error generating share link:', error);
                showNotification('Failed to generate share link', 'error');
                loading.classList.add('hidden');
            }
        }

        // Display share information
        function displayShareInfo(data) {
            const loading = document.getElementById('share-loading');
            const content = document.getElementById('share-content');
            const urlInput = document.getElementById('share-url-input');
            const viewCount = document.getElementById('share-view-count');
            const expiresText = document.getElementById('share-expires-text');
            
            urlInput.value = data.share_url;
            viewCount.textContent = data.view_count || 0;
            
            if (data.expires_at) {
                const expiresDate = new Date(data.expires_at);
                expiresText.textContent = `Expires ${expiresDate.toLocaleDateString()}`;
            } else {
                expiresText.textContent = 'Never expires';
            }
            
            loading.classList.add('hidden');
            content.classList.remove('hidden');
        }

        // Copy share link
        document.getElementById('copy-share-link').addEventListener('click', async () => {
            const input = document.getElementById('share-url-input');
            const btn = document.getElementById('copy-share-link');
            
            try {
                await navigator.clipboard.writeText(input.value);
                
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="las la-check"></i><span class="hidden sm:inline">Copied!</span>';
                btn.classList.add('bg-green-600', 'hover:bg-green-700');
                btn.classList.remove('bg-purple-600', 'hover:bg-purple-700');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('bg-green-600', 'hover:bg-green-700');
                    btn.classList.add('bg-purple-600', 'hover:bg-purple-700');
                }, 2000);
                
            } catch (err) {
                console.error('Failed to copy:', err);
                showNotification('Failed to copy link', 'error');
            }
        });

        // Regenerate share link with expiration
        document.getElementById('regenerate-share-link').addEventListener('click', async () => {
            const expirationSelect = document.getElementById('share-expiration');
            const expiresInDays = expirationSelect.value ? parseInt(expirationSelect.value) : null;
            
            if (!confirm('This will revoke the current link and create a new one. Continue?')) {
                return;
            }
            
            // First revoke current link
            await revokeShareLink(currentShareConversationId, false);
            
            // Then generate new link
            await generateShareLink(currentShareConversationId, expiresInDays);
        });

        // Revoke share link
        document.getElementById('revoke-share-link').addEventListener('click', async () => {
            if (!confirm('Are you sure you want to revoke this share link? Anyone with this link will no longer be able to access the conversation.')) {
                return;
            }
            
            await revokeShareLink(currentShareConversationId, true);
        });

        async function revokeShareLink(conversationId, closeModal = true) {
            try {
                const response = await fetch(`/multi-compare-conversation/${conversationId}/share`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to revoke share link');
                }
                
                showNotification('✓ Share link revoked successfully', 'success');
                
                if (closeModal) {
                    document.getElementById('share-modal').classList.add('hidden');
                }
                
            } catch (error) {
                console.error('Error revoking share link:', error);
                showNotification('Failed to revoke share link', 'error');
            }
        }

        // Close modal
        document.getElementById('close-share-modal').addEventListener('click', () => {
            document.getElementById('share-modal').classList.add('hidden');
        });

        // Close modal when clicking outside
        document.getElementById('share-modal').addEventListener('click', (e) => {
            if (e.target.id === 'share-modal') {
                document.getElementById('share-modal').classList.add('hidden');
            }
        });

        // Update expiration when select changes
        document.getElementById('share-expiration').addEventListener('change', function() {
            if (this.value) {
                showNotification('Click "Regenerate Link" to apply the expiration', 'info');
            }
        });
    </script>

    <script>
        // Toggle profile section
        document.getElementById('toggle-profile-section').addEventListener('click', function(e) {
            e.stopPropagation();
            const details = document.getElementById('profile-details');
            const chevron = document.getElementById('profile-chevron');
            
            details.classList.toggle('hidden');
            
            // Rotate chevron
            if (details.classList.contains('hidden')) {
                chevron.style.transform = 'rotate(0deg)';
            } else {
                chevron.style.transform = 'rotate(180deg)';
            }
        });

        // Close profile menu when clicking outside
        document.addEventListener('click', function(e) {
            const profileSection = document.getElementById('toggle-profile-section');
            const profileDetails = document.getElementById('profile-details');
            const chevron = document.getElementById('profile-chevron');
            
            // If clicking outside the profile section
            if (!profileSection.contains(e.target) && !profileDetails.contains(e.target)) {
                if (!profileDetails.classList.contains('hidden')) {
                    profileDetails.classList.add('hidden');
                    chevron.style.transform = 'rotate(0deg)';
                }
            }
        });

        // Close profile menu when clicking on a link
        document.querySelectorAll('#profile-details a').forEach(link => {
            link.addEventListener('click', function() {
                const details = document.getElementById('profile-details');
                const chevron = document.getElementById('profile-chevron');
                details.classList.add('hidden');
                chevron.style.transform = 'rotate(0deg)';
            });
        });

        // ====== MOBILE STATS POPOVER FUNCTIONALITY ======
        const statsMobileBtn = document.getElementById('stats-mobile-btn');
        const statsMobilePopover = document.getElementById('stats-mobile-popover');

        if (statsMobileBtn && statsMobilePopover) {
            // Toggle popover on button click
            statsMobileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                statsMobilePopover.classList.toggle('hidden');
            });

            // Close popover when clicking outside
            document.addEventListener('click', function(e) {
                if (!statsMobileBtn.contains(e.target) && !statsMobilePopover.contains(e.target)) {
                    statsMobilePopover.classList.add('hidden');
                }
            });
        }
    </script>

    {{-- User Page Time/ User Page timer, how long user was in the page --}}
    {{-- @include('admin.layouts.user_time_tracker') --}}

</body>
</html>
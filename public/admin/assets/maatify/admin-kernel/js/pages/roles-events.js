/**
 * Roles Page - Events Module
 * Handles all event listeners and user interactions
 */

(function() {
    'use strict';

    console.log('🎯 Roles Events Module - Initializing');

    // Wait for core module to be ready
    if (!window.RolesCore) {
        console.error('❌ RolesCore not found - make sure roles-core.js is loaded first');
        return;
    }

    const { capabilities, loadRoles, showAlert } = window.RolesCore;

    // ========================================================================
    // DOM Elements
    // ========================================================================
    const searchForm = document.getElementById('roles-search-form');
    const resetBtn = document.getElementById('btn-reset');
    const inputRoleId = document.getElementById('filter-role-id');
    const inputRoleName = document.getElementById('filter-role-name');
    const inputGroup = document.getElementById('filter-group');

    // ========================================================================
    // Event Listeners Setup
    // ========================================================================
    function setupEventListeners() {
        console.log('🎯 Setting up event listeners');

        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                console.log('🔍 Search form submitted');
                loadRoles();
            });
            console.log('  ├─ Search form listener: ✅');
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                console.log('🔄 Resetting filters');
                if (inputRoleId) inputRoleId.value = '';
                if (inputRoleName) inputRoleName.value = '';
                if (inputGroup) inputGroup.value = '';
                loadRoles();
            });
            console.log('  ├─ Reset button listener: ✅');
        }

        // ✅ Create Role button
        const createRoleBtn = document.getElementById('btn-create-role');
        if (createRoleBtn) {
            createRoleBtn.addEventListener('click', () => {
                if (window.RolesCreate) {
                    window.RolesCreate.openCreateModal();
                }
            });
            console.log('  ├─ Create role button listener: ✅');
        } else {
            console.log('  ├─ Create role button: ❌ (hidden - no permission)');
        }

        // ✅ Delegated action listeners
        document.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-metadata-btn');
            if (editBtn && window.RolesMetadata) {
                window.RolesMetadata.handleEditClick(editBtn);
                return;
            }

            const renameBtn = e.target.closest('.rename-role-btn');
            if (renameBtn && window.RolesRename) {
                window.RolesRename.handleRenameClick(renameBtn);
                return;
            }

            const toggleBtn = e.target.closest('.toggle-role-btn');
            if (toggleBtn && window.RolesToggle) {
                window.RolesToggle.handleToggleClick(toggleBtn);
                return;
            }
        });
        console.log('  ├─ Delegated action listeners: ✅');
        console.log('  └─ Event listeners setup complete');
    }

    // ========================================================================
    // Table Event Listeners
    // ========================================================================
    function setupTableEventListeners() {
        console.log('📊 Setting up table event listeners');
        document.addEventListener('tableAction', async (e) => {
            const { action, value, currentParams } = e.detail;
            console.log('━'.repeat(60));
            console.log("🔨 Table Event Received");
            console.log('━'.repeat(60));
            console.log('  ├─ Action:', action);
            console.log('  ├─ Value:', value);
            console.log('  └─ Current params:', JSON.stringify(currentParams, null, 2));

            let newParams = JSON.parse(JSON.stringify(currentParams));

            switch(action) {
                case 'pageChange':
                    newParams.page = value;
                    console.log('📄 Page change:', value);
                    break;

                case 'perPageChange':
                    newParams.per_page = value;
                    newParams.page = 1;
                    console.log('🔢 Per-page change:', value, '(reset to page 1)');
                    break;
            }

            // Clean empty values
            console.log('🧹 Cleaning empty search values...');
            if (newParams.search) {
                if (!newParams.search.global || !newParams.search.global.trim()) {
                    delete newParams.search.global;
                    console.log('  ├─ Removed empty global search');
                }

                if (newParams.search.columns) {
                    Object.keys(newParams.search.columns).forEach(key => {
                        if (!newParams.search.columns[key] || !newParams.search.columns[key].toString().trim()) {
                            delete newParams.search.columns[key];
                            console.log('  ├─ Removed empty column:', key);
                        }
                    });

                    if (Object.keys(newParams.search.columns).length === 0) {
                        delete newParams.search.columns;
                        console.log('  ├─ Removed empty columns object');
                    }
                }

                if (Object.keys(newParams.search).length === 0) {
                    delete newParams.search;
                    console.log('  └─ Removed empty search object');
                }
            }

            console.log('✅ Cleaned params:', JSON.stringify(newParams, null, 2));
            console.log('━'.repeat(60));

            // Use loadRolesWithParams from core if available
            if (window.RolesCore.loadRolesWithParams) {
                await window.RolesCore.loadRolesWithParams(newParams);
            }
        });
        console.log('  └─ Table event listener: ✅');
    }

    // ========================================================================
    // Initialize
    // ========================================================================
    function init() {
        setupEventListeners();
        setupTableEventListeners();
        loadRoles(); // Initial load
    }

    // Run initialization
    init();

    console.log('✅ Roles Events Module - Ready');

})();
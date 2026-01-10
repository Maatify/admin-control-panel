

/**
 * Created by Maatify.dev
 * User: Maatify.dev
 * Date: 2025-10-04
 * Time: 11:30 AM
 * https://www.Maatify.dev
 */

function Select2(elementOrSelector, data = [], options = {}) {
    const container = typeof elementOrSelector === 'string'
        ? document.querySelector(elementOrSelector)
        : elementOrSelector;

    if (!container) {
        console.error('Select2: Container not found');
        return null; // Return null if initialization fails
    }

    let isOpen = false;
    let selected = null;
    
    // Config defaults
    const config = {
        onChange: null,
        defaultValue: null,
        ...options
    };

    // Elements
    const box = container.querySelector('.js-select-box');
    const input = container.querySelector('.js-select-input');
    const dropdown = container.querySelector('.js-dropdown');
    const searchInput = container.querySelector('.js-search-input');
    const list = container.querySelector('.js-select-list');
    const arrow = container.querySelector('.js-arrow');

    function init() {
        if (!box || !dropdown) return;

        // Toggle dropdown
        box.addEventListener('click', (e) => {
            e.stopPropagation();
            toggle();
        });

        // Search input
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                renderList(e.target.value);
            });
            // Prevent closing when clicking search input
            searchInput.addEventListener('click', (e) => e.stopPropagation());
        }

        // Close when clicking outside
        document.addEventListener('click', handleDocumentClick);

        // Initial render
        renderList();
    }

    function handleDocumentClick(e) {
        if (isOpen && !container.contains(e.target)) {
            close();
        }
    }

    function toggle() {
        if (isOpen) {
            close();
        } else {
            open();
        }
    }

    function open() {
        isOpen = true;
        dropdown.classList.remove('hidden');
        
        // Active styles
        box.classList.add('border-blue-500', 'ring-1', 'ring-blue-500');
        box.classList.remove('border-gray-300', 'hover:border-gray-400');
        
        // Arrow rotation
        if (arrow) arrow.classList.add('rotate-180');

        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
            renderList();
        }
    }

    function close() {
        isOpen = false;
        dropdown.classList.add('hidden');
        
        // Remove active styles
        box.classList.remove('border-blue-500', 'ring-1', 'ring-blue-500');
        box.classList.add('border-gray-300', 'hover:border-gray-400');

        // Arrow rotation reset
        if (arrow) arrow.classList.remove('rotate-180');
    }

    function renderList(filter = '') {
        if (!list) return;
        list.innerHTML = '';

        const filterLower = filter.toLowerCase();
        const filtered = data.filter(item =>
            item.label.toLowerCase().includes(filterLower)
        );

        if (filtered.length === 0) {
            const li = document.createElement('li');
            li.className = 'p-2 text-gray-400 cursor-default text-center text-sm';
            li.textContent = 'No results found';
            list.appendChild(li);
            return;
        }

        filtered.forEach(item => {
            const li = document.createElement('li');
            // Base styles
            li.className = 'p-2 cursor-pointer text-gray-700 hover:bg-gray-100 flex items-center justify-between transition-colors duration-150 rounded-sm';
            
            if (selected && selected.value === item.value) {
                // Selected styles
                li.classList.add('bg-blue-50', 'text-blue-600', 'font-medium');
            }

            li.innerHTML = `<span>${item.label}</span>`;
            if (selected && selected.value === item.value) {
                 li.innerHTML += `<span class="text-blue-600 font-bold">&#10003;</span>`; // Checkmark
            }

            li.addEventListener('click', (e) => {
                e.stopPropagation();
                select(item);
            });
            list.appendChild(li);
        });
    }

    function select(item) {
        selected = item;
        if (input) {
            input.value = item.label;
            // Dispatch event for other scripts to listen to
            input.dispatchEvent(new Event('change'));
        }
        // Also update data attribute on container for easy access
        container.dataset.value = item.value;
        console.log(item.value);
        close();
    }

    function getValue() {
        return selected ? selected.value : null;
    }
    
    function getSelected() {
        return selected;
    }

    function destroy() {
        document.removeEventListener('click', handleDocumentClick);
        // Additional cleanup could go here
    }

    // Initialize
    init();

    // Set default value if provided
    if (config.defaultValue) {
        const defaultItem = data.find(item => item.value === config.defaultValue);
        if (defaultItem) {
            select(defaultItem);
        }
    }

    // Return Public API
    return {
        open,
        close,
        getValue, // Returns the value string
        getSelected, // Returns the full item object
        destroy
    };
}

// Global exposure
window.Select2 = Select2;

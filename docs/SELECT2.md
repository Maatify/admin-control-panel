# Custom Select2 Component

A lightweight, Tailwind-styled custom select component utilizing the Factory Pattern.

## Features
- **Tailwind CSS**: Fully styled with Tailwind utility classes.
- **Factory Pattern**: initialized via `Select2()` function.
- **Searchable**: Built-in search functionality.
- **Methods**: Easy API to get values and objects.

## Usage

### 1. HTML Structure

Ensure your HTML follows this structure. The `js-` prefixed classes are required for the JavaScript to function.

```html
<div class="relative w-full min-w-[250px] text-sm text-gray-700" id="mySelectContainer">
    <!-- Select Box Trigger -->
    <div class="js-select-box flex items-center justify-between border border-gray-300 rounded-md p-2 cursor-pointer bg-white transition-all shadow-sm hover:border-gray-400 group">
        <input type="text" class="js-select-input border-none outline-none flex-1 cursor-pointer bg-transparent w-full text-current placeholder-gray-400" placeholder="Search or select..." readonly/>
        <span class="js-arrow ml-2 text-gray-500 text-xs transition-transform duration-200">▼</span>
    </div>

    <!-- Dropdown (Hidden by default) -->
    <div class="js-dropdown hidden absolute top-[calc(100%+0.25rem)] left-0 right-0 border border-gray-200 rounded-md bg-white z-50 shadow-lg overflow-hidden ring-1 ring-black ring-opacity-5">
        <div class="p-2 border-b border-gray-100 bg-gray-50">
            <input type="text" class="js-search-input w-full p-2 border border-gray-300 rounded outline-none text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Search..."/>
        </div>
        <ul class="js-select-list max-h-60 overflow-y-auto p-0 m-0 list-none">
            <!-- Options rendered via JS -->
        </ul>
    </div>
</div>
```

### 2. Initialization

Include `select2.js` and initialize the component.

```javascript
// Data format
const options = [
    { value: '1', label: 'Option One' },
    { value: '2', label: 'Option Two' }
];

// Initialize
const mySelect = Select2('#mySelectContainer', options);
```

### 3. Retrieving Data

There are two ways to get the selected value.

#### Method A: Using the Instance (Recommended)
If you saved the instance to a variable:

```javascript
// Get the ID (e.g., "1")
const selectedId = mySelect.getValue();

// Get the full option object (e.g., { value: "1", label: "Option One" })
const selectedItem = mySelect.getSelected();

console.log(selectedId);
```

#### Method B: Using the DOM
The component automatically updates a `data-value` attribute on the container element.

```javascript
const container = document.getElementById('mySelectContainer');
const value = container.dataset.value; 

console.log(value); 
```

**Note:** The visible input field only contains the display text (label), NOT the value ID. Do not read the input value if you want the ID.

### 4. API Methods

| Method | Description |
| :--- | :--- |
| `open()` | Opens the dropdown. |
| `close()` | Closes the dropdown. |
| `getValue()` | Returns the selected `value` (ID). |
| `getSelected()` | Returns the selected item object `{value, label}`. |
| `destroy()` | Removes event listeners. |

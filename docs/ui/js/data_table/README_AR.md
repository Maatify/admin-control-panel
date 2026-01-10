# توثيق جدول البيانات (Data Table)

`data_table.js` هو مكون شامل لعرض الجداول الديناميكية مع دعم ترقيم الصفحات من جانب الخادم (Server-side Pagination)، والفرز، والتصفية، وتصدير البيانات.

## مثال على التكامل (Integration Example)

### 1. هيكل HTML
تأكد من وجود حاوية حيث سيتم عرض الجدول.
```html
<div class="container mt-6">
    <div id="table-container" class="w-full"></div>
</div>

<!-- تضمين السكربت -->
<script src="/assets/js/data_table.js"></script>
```

### 2. تهيئة جافاسكريبت
```javascript
document.addEventListener('DOMContentLoaded', () => {
    // 1. تعريف ترويسات الأعمدة (أسماء العرض)
    const headers = [
        "معرف المستخدم",
        "الاسم الكامل", 
        "البريد الإلكتروني",
        "حالة الحساب"
    ];

    // 2. تعريف مفاتيح الصفوف (مفاتيح JSON من استجابة API)
    const rows = [
        "id",
        "full_name",
        "email",
        "status"
    ];

    // 3. تعريف المعاملات الأولية
    const params = {
        per_page: 10,
        filters: {} 
    };

    // 4. تهيئة الجدول
    // يجلب البيانات من /api/users/list
    createTable("users/list", params, headers, rows);
});
```

## الدوال العامة

### `createTable(apiUrl, params, headers, rows)`
تقوم بتهيئة أو تحديث بيانات الجدول.
- **apiUrl**: مسار نقطة النهاية (مثال: `"sessions/query"`).
- **params**: كائن يحتوي على `per_page` والمرشحات (filters) وغيرها.
- **headers**: مصفوفة بأسماء عرض الأعمدة.
- **rows**: مصفوفة بمفاتيح الكائنات المقابلة للأعمدة.
- **السلوك**: تجلب البيانات من `/api/{apiUrl}` وتستدعي `TableComponent`.

## `TableComponent(data, columns, rowNames, pagination)`
تقوم بإنشاء منطق HTML للجدول.
- **data**: مصفوفة كائنات الصفوف.
- **columns**: تسميات ترويسة الجدول.
- **rowNames**: المفاتيح للوصول للبيانات في كائنات الصف.
- **pagination**: كائن `{ count, page, total }`.

### المميزات
1.  **العرض (Rendering)**: تولد هيكل HTML للجدول والترويسة والصفوف.
2.  **منطق الشارات (Badge Logic)**: تنسيق تلقائي لحقول معينة مثل "status" (active=أخضر، draft=أحمر، إلخ).
3.  **ترقيم الصفحات (Pagination)**: تعرض أزرار التالي/السابق وأرقام الصفحات. تعالج تغيير الصفحة عبر `updatePage`.
4.  **الفرز (Sorting)**: ترويسات قابلة للنقر لفرز البيانات من جانب العميل.
5.  **التصفية (Filtering)**:
    - **بحث نصي**: تصفية نصية بسيطة من جانب العميل.
    - **تصفية الحالة**: أزرار لـ "Active" و "Draft" وما إلى ذلك.
6.  **التصدير (Export)**:
    - **CSV**: تنزيل ملف CSV من جانب العميل.
    - **Excel**: تنزيل ملف XLS من جانب العميل.
    - **PDF**: فتح عرض الطباعة لإنشاء PDF.

## الاعتماديات
- **Axios/Fetch**: تستخدم `fetch` لطلبات API.
- **Tailwind/Bootstrap**: تعتمد على فئات CSS محددة للتنسيق.

/**
 * DateTimePicker Handler
 * Wrapper فوق Flatpickr بيوفر API بسيط وثابت للشغل
 *
 * Dependencies:
 *   - flatpickr.min.js  (تحمله من: https://cdn.jsdelivr.net/npm/flatpickr)
 *   - flatpickr.min.css (تحمله من: https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css)
 */

class DateTimePicker {
  /**
   * @param {string|HTMLElement} selector - CSS selector أو element مباشرة
   * @param {object} options - إعدادات إضافية
   */
  constructor(selector, options = {}) {
    this._element = typeof selector === "string"
        ? document.querySelector(selector)
        : selector;

    if (!this._element) {
      console.error(`[DateTimePicker] العنصر مش موجود: ${selector}`);
      return;
    }

    // الإعدادات الافتراضية
    const defaults = {
      locale: "en",           // اللغة العربية (محتاج تحمل flatpickr/dist/l10n/ar.js)
      dateFormat: "Y-m-d",    // الفورمت اللي بيتبعت للسيرفر
      altInput: true,          // بيعرض فورمت تاني للمستخدم (أجمل)
      altFormat: "j F Y",      // الفورمت اللي بيشوفه المستخدم
      allowInput: true,        // يخلي المستخدم يكتب بيده
      disableMobile: false,    // يستخدم native picker على الموبايل
      ...options,
    };

    // ربط الـ callbacks بـ this
    this._onChange  = options.onChange  || null;
    this._onOpen    = options.onOpen    || null;
    this._onClose   = options.onClose   || null;
    this._onReady   = options.onReady   || null;

    // إزالة الـ callbacks من الـ options قبل ما نبعتها لـ flatpickr
    const { onChange, onOpen, onClose, onReady, ...flatpickrOptions } = defaults;

    this._instance = flatpickr(this._element, {
      ...flatpickrOptions,
      onChange:  (selectedDates, dateStr, instance) => this._handleChange(selectedDates, dateStr, instance),
      onOpen:    (selectedDates, dateStr, instance) => this._handleOpen(selectedDates, dateStr, instance),
      onClose:   (selectedDates, dateStr, instance) => this._handleClose(selectedDates, dateStr, instance),
      onReady:   (selectedDates, dateStr, instance) => this._handleReady(selectedDates, dateStr, instance),
    });
  }

  // ─── Private Handlers ───────────────────────────────────────────────────────

  _handleChange(selectedDates, dateStr, instance) {
    if (typeof this._onChange === "function") {
      this._onChange({
        dates: selectedDates,
        dateStr,
        value: this.getValue(),
        instance,
      });
    }
  }

  _handleOpen(selectedDates, dateStr, instance) {
    if (typeof this._onOpen === "function") {
      this._onOpen({ dates: selectedDates, dateStr, instance });
    }
  }

  _handleClose(selectedDates, dateStr, instance) {
    if (typeof this._onClose === "function") {
      this._onClose({ dates: selectedDates, dateStr, instance });
    }
  }

  _handleReady(selectedDates, dateStr, instance) {
    if (typeof this._onReady === "function") {
      this._onReady({ dates: selectedDates, dateStr, instance });
    }
  }

  // ─── Public API ─────────────────────────────────────────────────────────────

  /** بيرجع التاريخ المختار كـ string بالفورمت المحدد */
  getValue() {
    return this._instance?.input?.value ?? "";
  }

  /** بيرجع التاريخ المختار كـ Date object */
  getDate() {
    return this._instance?.selectedDates[0] ?? null;
  }

  /** بيرجع كل التواريخ المختارة (في حالة range أو multi) */
  getDates() {
    return this._instance?.selectedDates ?? [];
  }

  /**
   * بيحدد تاريخ برمجياً
   * @param {string|Date} date
   * @param {boolean} triggerChange - يشغّل الـ onChange ولا لأ (default: false)
   */
  setValue(date, triggerChange = false) {
    this._instance?.setDate(date, triggerChange);
  }

  /** بيمسح الاختيار */
  clear() {
    this._instance?.clear();
  }

  /** بيفتح التقويم */
  open() {
    this._instance?.open();
  }

  /** بيقفل التقويم */
  close() {
    this._instance?.close();
  }

  /**
   * بيحدد تاريخ أدنى (مش هيقدر يختار قبله)
   * @param {string|Date} date
   */
  setMinDate(date) {
    this._instance?.set("minDate", date);
  }

  /**
   * بيحدد تاريخ أقصى (مش هيقدر يختار بعده)
   * @param {string|Date} date
   */
  setMaxDate(date) {
    this._instance?.set("maxDate", date);
  }

  /**
   * بيعطل أو يفعّل الـ picker
   * @param {boolean} disabled
   */
  setDisabled(disabled) {
    if (this._instance) {
      this._instance.input.disabled = disabled;
    }
  }

  /** بيرجع الـ flatpickr instance الأصلي لو محتاج تعمل حاجة متقدمة */
  getInstance() {
    return this._instance;
  }

  /** بيدمر الـ picker وينظف الـ DOM */
  destroy() {
    this._instance?.destroy();
    this._instance = null;
  }
}


// ─── Factory Functions (للاستخدام السريع) ──────────────────────────────────

/**
 * Date Picker فقط (بدون وقت)
 */
function createDatePicker(selector, options = {}) {
  return new DateTimePicker(selector, {
    dateFormat: "Y-m-d",
    altFormat: "j F Y",
    ...options,
  });
}

/**
 * DateTime Picker (تاريخ + وقت)
 */
function createDateTimePicker(selector, options = {}) {
  return new DateTimePicker(selector, {
    enableTime: true,
    dateFormat: "Y-m-d H:i",
    altFormat: "j F Y - h:i K",
    time_24hr: false,
    ...options,
  });
}

/**
 * Time Picker فقط (وقت بدون تاريخ)
 */
function createTimePicker(selector, options = {}) {
  return new DateTimePicker(selector, {
    enableTime: true,
    noCalendar: true,
    dateFormat: "H:i",
    altFormat: "h:i K",
    time_24hr: false,
    ...options,
  });
}

/**
 * Date Range Picker (فترة من تاريخ لتاريخ)
 */
function createDateRangePicker(selector, options = {}) {
  return new DateTimePicker(selector, {
    mode: "range",
    dateFormat: "Y-m-d",
    altFormat: "j F Y",
    ...options,
  });
}


// ─── Auto Init ───────────────────────────────────────────────────────────────
//
// بيمسح أوتوماتيك كل عنصر عنده class="date-picker" في الـ page
// ويعمله picker من غير ما تكتب أي كود في الـ page نفسها.
//
// الـ HTML:
//   <input class="date-picker" data-role="from" ... />
//   <input class="date-picker" data-role="to"   ... />
//
// data-role:
//   "from" → بيتحكم في الـ minDate بتاع الـ "to"
//   "to"   → بيتحكم في الـ maxDate بتاع الـ "from"
//   أي قيمة تانية → picker عادي مستقل بدون sync
//
// data-reset-btn (اختياري):
//   id بتاع زرار الـ reset عشان الـ auto-init يمسحه
//   مثال: <form data-reset-btn="my-reset-btn-id">
// ─────────────────────────────────────────────────────────────────────────────

class DatePickerAutoInit {

  constructor(scope = document) {
    this._pickers = {};   // { role: DateTimePicker }
    this._scope   = scope;

    this._init();
    this._bindResetBtn();
  }

  _init() {
    this._scope.querySelectorAll(".date-picker").forEach((el) => {
      const role = el.dataset.role ?? el.id ?? Math.random().toString(36).slice(2);

      this._pickers[role] = new DateTimePicker(el, {
        dateFormat: "Y-m-d",
        altInput:   true,
        altFormat:  "d M Y",
        maxDate:    "today",
        onChange: ({ dateStr }) => this._syncBounds(role, dateStr),
      });
    });
  }

  _syncBounds(changedRole, dateStr) {
    if (changedRole === "from") {
      this._pickers.to?.setMinDate(dateStr || null);
    } else if (changedRole === "to") {
      this._pickers.from?.setMaxDate(dateStr || "today");
    }
  }

  _bindResetBtn() {
    const btnId = this._scope.querySelector("form")?.dataset.resetBtn;
    const btn   = btnId
        ? document.getElementById(btnId)
        : this._scope.querySelector(".date-picker-reset");

    btn?.addEventListener("click", () => this._reset());
  }

  _reset() {
    Object.values(this._pickers).forEach(p => p.clear());
    this._pickers.from?.setMaxDate("today");
    this._pickers.to?.setMinDate(null);
  }

  /** لو عايز تاخد الـ picker بتاع role معين برمجياً */
  get(role) {
    return this._pickers[role] ?? null;
  }
}

// ─── تشغيل أوتوماتيك عند تحميل الصفحة ───────────────────────────────────────

document.addEventListener("DOMContentLoaded", () => {
  if (document.querySelector(".date-picker")) {
    window.datePickerAutoInit = new DatePickerAutoInit();
  }
});
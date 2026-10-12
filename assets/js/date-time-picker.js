/**
 * assets/js/date-time-picker.js
 * iCareDonate Unified Custom Date & Time Picker System
 * Zero external libraries — Pure vanilla JS, fully accessible, brand-styled.
 */

(function (window, document) {
  'use strict';

  var MONTH_NAMES = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];
  var MONTH_ABBR = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var WEEKDAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

  function pad(n) {
    return String(n).padStart(2, '0');
  }

  function parseYMD(str) {
    if (!str) return null;
    var parts = str.split('-');
    if (parts.length !== 3) return null;
    return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
  }

  function formatYMD(d) {
    if (!d) return '';
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
  }

  function formatReadable(d) {
    if (!d) return '';
    return MONTH_ABBR[d.getMonth()] + ' ' + pad(d.getDate()) + ', ' + d.getFullYear();
  }

  /* ============================================================
     1. Date Picker
     ============================================================ */
  function initIcdDatePicker(config) {
    var trigger   = typeof config.trigger === 'string' ? document.getElementById(config.trigger) : config.trigger;
    var popover   = typeof config.popover === 'string' ? document.getElementById(config.popover) : config.popover;
    var display   = typeof config.display === 'string' ? document.getElementById(config.display) : config.display;
    var hidden    = typeof config.hidden === 'string' ? document.getElementById(config.hidden) : config.hidden;
    if (!trigger || !popover || !display || !hidden) return;

    var today = new Date();
    today.setHours(0, 0, 0, 0);

    var minDate = config.minDate ? parseYMD(config.minDate) : today;
    if (minDate) minDate.setHours(0, 0, 0, 0);

    var selectedDate = hidden.value ? parseYMD(hidden.value) : (config.initialDate ? parseYMD(config.initialDate) : null);
    if (selectedDate) selectedDate.setHours(0, 0, 0, 0);

    var viewYear  = selectedDate ? selectedDate.getFullYear() : today.getFullYear();
    var viewMonth = selectedDate ? selectedDate.getMonth() : today.getMonth();

    function renderCalendar() {
      popover.innerHTML = '';

      // Header
      var header = document.createElement('div');
      header.className = 'icd-cal-header';

      var title = document.createElement('div');
      title.className = 'icd-cal-title';
      title.textContent = MONTH_NAMES[viewMonth] + ' ' + viewYear;
      header.appendChild(title);

      var nav = document.createElement('div');
      nav.className = 'icd-cal-nav';

      var prevBtn = document.createElement('button');
      prevBtn.type = 'button';
      prevBtn.className = 'icd-cal-btn';
      prevBtn.setAttribute('aria-label', 'Previous month');
      prevBtn.innerHTML = '&#9664;';
      if (minDate) {
        var prevMonthDate = new Date(viewYear, viewMonth, 0);
        if (prevMonthDate < minDate) {
          prevBtn.disabled = true;
        }
      }
      prevBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        viewMonth--;
        if (viewMonth < 0) {
          viewMonth = 11;
          viewYear--;
        }
        renderCalendar();
      });
      nav.appendChild(prevBtn);

      var nextBtn = document.createElement('button');
      nextBtn.type = 'button';
      nextBtn.className = 'icd-cal-btn';
      nextBtn.setAttribute('aria-label', 'Next month');
      nextBtn.innerHTML = '&#9654;';
      nextBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        viewMonth++;
        if (viewMonth > 11) {
          viewMonth = 0;
          viewYear++;
        }
        renderCalendar();
      });
      nav.appendChild(nextBtn);

      header.appendChild(nav);
      popover.appendChild(header);

      // Weekday Row
      var weekdaysRow = document.createElement('div');
      weekdaysRow.className = 'icd-cal-weekdays';
      WEEKDAYS.forEach(function (wd) {
        var wdEl = document.createElement('div');
        wdEl.className = 'icd-cal-weekday';
        wdEl.textContent = wd;
        weekdaysRow.appendChild(wdEl);
      });
      popover.appendChild(weekdaysRow);

      // Days Grid
      var grid = document.createElement('div');
      grid.className = 'icd-cal-grid';

      var firstDay = new Date(viewYear, viewMonth, 1).getDay();
      var daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
      var prevMonthDays = new Date(viewYear, viewMonth, 0).getDate();

      // Leading days from previous month
      for (var p = firstDay - 1; p >= 0; p--) {
        var dayNum = prevMonthDays - p;
        var pDay = document.createElement('button');
        pDay.type = 'button';
        pDay.className = 'icd-cal-day is-other-month is-disabled';
        pDay.textContent = dayNum;
        pDay.disabled = true;
        grid.appendChild(pDay);
      }

      // Days in current month
      for (var d = 1; d <= daysInMonth; d++) {
        (function (day) {
          var curr = new Date(viewYear, viewMonth, day);
          curr.setHours(0, 0, 0, 0);

          var dayBtn = document.createElement('button');
          dayBtn.type = 'button';
          dayBtn.className = 'icd-cal-day';
          dayBtn.textContent = day;

          if (minDate && curr < minDate) {
            dayBtn.classList.add('is-disabled');
            dayBtn.disabled = true;
          }

          if (curr.getTime() === today.getTime()) {
            dayBtn.classList.add('is-today');
          }

          if (selectedDate && curr.getTime() === selectedDate.getTime()) {
            dayBtn.classList.add('is-selected');
          }

          dayBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            selectedDate = curr;
            var valYmd = formatYMD(selectedDate);
            hidden.value = valYmd;
            display.textContent = formatReadable(selectedDate);
            display.classList.remove('is-placeholder');
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
            close();
          });

          grid.appendChild(dayBtn);
        })(d);
      }

      popover.appendChild(grid);

      // Footer
      var footer = document.createElement('div');
      footer.className = 'icd-cal-footer';

      var todayLink = document.createElement('button');
      todayLink.type = 'button';
      todayLink.className = 'icd-cal-link';
      todayLink.textContent = 'Today';
      todayLink.addEventListener('click', function (e) {
        e.stopPropagation();
        selectedDate = new Date(today);
        viewYear = selectedDate.getFullYear();
        viewMonth = selectedDate.getMonth();
        var valYmd = formatYMD(selectedDate);
        hidden.value = valYmd;
        display.textContent = formatReadable(selectedDate);
        display.classList.remove('is-placeholder');
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
        close();
      });
      footer.appendChild(todayLink);

      var clearLink = document.createElement('button');
      clearLink.type = 'button';
      clearLink.className = 'icd-cal-link';
      clearLink.style.color = 'var(--color-text-muted)';
      clearLink.textContent = 'Clear';
      clearLink.addEventListener('click', function (e) {
        e.stopPropagation();
        selectedDate = null;
        hidden.value = '';
        display.textContent = config.placeholder || 'Select date';
        display.classList.add('is-placeholder');
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
        close();
      });
      footer.appendChild(clearLink);

      popover.appendChild(footer);
    }

    function open() {
      popover.style.display = 'block';
      trigger.classList.add('is-active');
      trigger.setAttribute('aria-expanded', 'true');
      if (selectedDate) {
        viewYear = selectedDate.getFullYear();
        viewMonth = selectedDate.getMonth();
      }
      renderCalendar();
    }

    function close() {
      popover.style.display = 'none';
      trigger.classList.remove('is-active');
      trigger.setAttribute('aria-expanded', 'false');
    }

    function toggle() {
      popover.style.display === 'block' ? close() : open();
    }

    trigger.addEventListener('click', toggle);
    trigger.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggle();
      } else if (e.key === 'Escape') {
        close();
      }
    });

    document.addEventListener('click', function (e) {
      if (!trigger.contains(e.target) && !popover.contains(e.target)) {
        close();
      }
    });

    // Init state
    if (selectedDate) {
      display.textContent = formatReadable(selectedDate);
      display.classList.remove('is-placeholder');
    } else {
      display.textContent = config.placeholder || 'Select date';
      display.classList.add('is-placeholder');
    }
  }

  /* ============================================================
     2. Time Picker
     ============================================================ */
  function initIcdTimePicker(config) {
    var trigger   = typeof config.trigger === 'string' ? document.getElementById(config.trigger) : config.trigger;
    var popover   = typeof config.popover === 'string' ? document.getElementById(config.popover) : config.popover;
    var display   = typeof config.display === 'string' ? document.getElementById(config.display) : config.display;
    var hidden    = typeof config.hidden === 'string' ? document.getElementById(config.hidden) : config.hidden;
    if (!trigger || !popover || !display || !hidden) return;

    var MINUTES = [0, 15, 30, 45]; // Sensible 15-minute intervals per prompt

    // Parse initial value (default: 9:00 AM)
    var state = { hour: 9, minute: 0, period: 'AM' };
    if (hidden.value) {
      var parts = hidden.value.split(':');
      if (parts.length >= 2) {
        var h24 = parseInt(parts[0], 10);
        var m   = parseInt(parts[1], 10);
        state.period = h24 >= 12 ? 'PM' : 'AM';
        state.hour   = h24 % 12 === 0 ? 12 : h24 % 12;
        // Nearest 15
        var closestM = MINUTES[0];
        var minDiff = 999;
        MINUTES.forEach(function (opt) {
          if (Math.abs(opt - m) < minDiff) {
            minDiff = Math.abs(opt - m);
            closestM = opt;
          }
        });
        state.minute = closestM;
      }
    }

    function syncValue() {
      display.textContent = pad(state.hour) + ':' + pad(state.minute) + ' ' + state.period;
      display.classList.remove('is-placeholder');
      var h24 = (state.hour % 12) + (state.period === 'PM' ? 12 : 0);
      hidden.value = pad(h24) + ':' + pad(state.minute);
      hidden.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function renderTime() {
      popover.innerHTML = '';

      var header = document.createElement('div');
      header.className = 'icd-time-header';
      header.textContent = 'Select Available Time';
      popover.appendChild(header);

      var grid = document.createElement('div');
      grid.className = 'icd-time-grid';

      // Hours col (01 to 12)
      var hCol = document.createElement('div');
      hCol.className = 'icd-time-col';
      for (var h = 1; h <= 12; h++) {
        (function (hh) {
          var pill = document.createElement('div');
          pill.className = 'icd-time-pill' + (hh === state.hour ? ' is-selected' : '');
          pill.textContent = pad(hh);
          pill.addEventListener('click', function (e) {
            e.stopPropagation();
            state.hour = hh;
            syncValue();
            renderTime();
          });
          hCol.appendChild(pill);
        })(h);
      }
      grid.appendChild(hCol);

      // Separator
      var sep = document.createElement('div');
      sep.className = 'icd-time-sep';
      sep.textContent = ':';
      grid.appendChild(sep);

      // Minutes col (00, 15, 30, 45)
      var mCol = document.createElement('div');
      mCol.className = 'icd-time-col';
      MINUTES.forEach(function (mm) {
        var pill = document.createElement('div');
        pill.className = 'icd-time-pill' + (mm === state.minute ? ' is-selected' : '');
        pill.textContent = pad(mm);
        pill.addEventListener('click', function (e) {
          e.stopPropagation();
          state.minute = mm;
          syncValue();
          renderTime();
        });
        mCol.appendChild(pill);
      });
      grid.appendChild(mCol);

      // Spacing col
      var blank = document.createElement('div');
      grid.appendChild(blank);

      // AM/PM col
      var pCol = document.createElement('div');
      pCol.className = 'icd-time-col';
      ['AM', 'PM'].forEach(function (pp) {
        var pill = document.createElement('div');
        pill.className = 'icd-time-pill' + (pp === state.period ? ' is-selected' : '');
        pill.textContent = pp;
        pill.addEventListener('click', function (e) {
          e.stopPropagation();
          state.period = pp;
          syncValue();
          renderTime();
        });
        pCol.appendChild(pill);
      });
      grid.appendChild(pCol);

      popover.appendChild(grid);

      // Footer with Done button
      var footer = document.createElement('div');
      footer.className = 'icd-time-footer';

      var doneBtn = document.createElement('button');
      doneBtn.type = 'button';
      doneBtn.className = 'icd-btn-done';
      doneBtn.textContent = 'Done';
      doneBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        close();
      });
      footer.appendChild(doneBtn);

      popover.appendChild(footer);
    }

    function open() {
      popover.style.display = 'block';
      trigger.classList.add('is-active');
      trigger.setAttribute('aria-expanded', 'true');
      renderTime();
    }

    function close() {
      popover.style.display = 'none';
      trigger.classList.remove('is-active');
      trigger.setAttribute('aria-expanded', 'false');
    }

    function toggle() {
      popover.style.display === 'block' ? close() : open();
    }

    trigger.addEventListener('click', toggle);
    trigger.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggle();
      } else if (e.key === 'Escape') {
        close();
      }
    });

    document.addEventListener('click', function (e) {
      if (!trigger.contains(e.target) && !popover.contains(e.target)) {
        close();
      }
    });

    syncValue();
  }

  /* Expose globals */
  window.initIcdDatePicker = initIcdDatePicker;
  window.initIcdTimePicker = initIcdTimePicker;

})(window, document);

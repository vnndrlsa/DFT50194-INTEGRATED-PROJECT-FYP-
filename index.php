<!-- CSS for Calendar -->
<style>
    .date-input-wrapper {
        position: relative;
        display: flex;
        gap: 0;
        max-width: 400px;
    }

    .date-input {
        flex: 1;
        padding: 12px 16px;
        border: 2px solid #4a90e2;
        border-right: none;
        border-radius: 4px 0 0 4px;
        font-size: 14px;
    }

    .calendar-btn {
        padding: 12px 16px;
        background: white;
        border: 2px solid #4a90e2;
        border-left: none;
        border-radius: 0 4px 4px 0;
        cursor: pointer;
        transition: background 0.3s;
    }

    .calendar-btn:hover {
        background: #f0f0f0;
    }

    .calendar-icon {
        width: 20px;
        height: 20px;
    }

    .calendar-popup {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        margin-top: 4px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        padding: 16px;
        width: 320px;
    }

    .calendar-popup.active {
        display: block;
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        gap: 8px;
    }

    .calendar-nav {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .calendar-nav:hover {
        background: #f0f0f0;
    }

    .calendar-select {
        padding: 6px 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
    }

    .calendar-grid {
        width: 100%;
    }
    
    .calendar-days-container {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
    }

    .calendar-day-header {
        text-align: center;
        font-weight: 600;
        color: #666;
        font-size: 12px;
        padding: 8px 0;
    }

    .calendar-day {
        text-align: center;
        padding: 10px;
        cursor: pointer;
        border-radius: 4px;
        font-size: 14px;
        transition: background 0.2s;
    }

    .calendar-day:hover {
        background: #e3f2fd;
    }

    .calendar-day.today {
        background: #8b7d3a;
        color: white;
        font-weight: 600;
    }

    .calendar-day.selected {
        background: #4a90e2;
        color: white;
    }

    .calendar-day.empty {
        cursor: default;
    }

    .calendar-day.empty:hover {
        background: transparent;
    }
</style>

<?php
// PHP Variables for Calendar
$currentMonth = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Generate years (current year ± 10)
$years = range($currentYear - 10, $currentYear + 10);
$months = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
];
?>

<!-- Calendar HTML -->
<div class="date-input-wrapper">
    <input type="text" 
           class="date-input" 
           id="dateInput" 
           name="expiry_date"
           placeholder="dd.mm.yyyy"
           readonly>
    <button type="button" class="calendar-btn" id="calendarBtn">
        <svg class="calendar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
    </button>
    
    <div class="calendar-popup" id="calendarPopup">
        <div class="calendar-header">
            <button class="calendar-nav" id="prevMonth">◄</button>
            <select class="calendar-select" id="monthSelect">
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?= $num ?>" <?= $num == $currentMonth ? 'selected' : '' ?>>
                        <?= $name ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="calendar-select" id="yearSelect">
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>" <?= $year == $currentYear ? 'selected' : '' ?>>
                        <?= $year ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="calendar-nav" id="nextMonth">►</button>
        </div>
        
        <div class="calendar-grid">
            <div class="calendar-days-container">
                <div class="calendar-day-header">Su</div>
                <div class="calendar-day-header">Mo</div>
                <div class="calendar-day-header">Tu</div>
                <div class="calendar-day-header">We</div>
                <div class="calendar-day-header">Th</div>
                <div class="calendar-day-header">Fr</div>
                <div class="calendar-day-header">Sa</div>
            </div>
            
            <div class="calendar-days-container" id="calendarDays"></div>
        </div>
    </div>
</div>

<!-- JavaScript for Calendar -->
<script>
    const calendarBtn = document.getElementById('calendarBtn');
    const calendarPopup = document.getElementById('calendarPopup');
    const dateInput = document.getElementById('dateInput');
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const prevMonth = document.getElementById('prevMonth');
    const nextMonth = document.getElementById('nextMonth');
    const calendarDays = document.getElementById('calendarDays');

    let selectedDate = null;
    let currentMonth = <?= $currentMonth ?>;
    let currentYear = <?= $currentYear ?>;

    // Toggle calendar
    calendarBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        calendarPopup.classList.toggle('active');
        renderCalendar();
    });

    // Close when clicking outside
    document.addEventListener('click', function(e) {
        if (!calendarPopup.contains(e.target) && e.target !== calendarBtn) {
            calendarPopup.classList.remove('active');
        }
    });

    // Month change
    monthSelect.addEventListener('change', function() {
        currentMonth = parseInt(this.value);
        renderCalendar();
    });

    // Year change
    yearSelect.addEventListener('change', function() {
        currentYear = parseInt(this.value);
        renderCalendar();
    });

    // Previous month
    prevMonth.addEventListener('click', function() {
        currentMonth--;
        if (currentMonth < 1) {
            currentMonth = 12;
            currentYear--;
        }
        monthSelect.value = currentMonth;
        yearSelect.value = currentYear;
        renderCalendar();
    });

    // Next month
    nextMonth.addEventListener('click', function() {
        currentMonth++;
        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        }
        monthSelect.value = currentMonth;
        yearSelect.value = currentYear;
        renderCalendar();
    });

    function renderCalendar() {
        calendarDays.innerHTML = '';
        
        const firstDay = new Date(currentYear, currentMonth - 1, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
        
        const today = new Date();
        const isCurrentMonth = currentMonth === (today.getMonth() + 1) && currentYear === today.getFullYear();
        const todayDate = today.getDate();

        // Empty cells
        for (let i = 0; i < firstDay; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'calendar-day empty';
            calendarDays.appendChild(emptyDay);
        }

        // Days
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.textContent = day;
            
            if (isCurrentMonth && day === todayDate) {
                dayElement.classList.add('today');
            }

            dayElement.addEventListener('click', function() {
                selectedDate = {
                    day: day,
                    month: currentMonth,
                    year: currentYear
                };
                
                const formattedDate = String(day).padStart(2, '0') + '.' + 
                                     String(currentMonth).padStart(2, '0') + '.' + 
                                     currentYear;
                dateInput.value = formattedDate;
                
                document.querySelectorAll('.calendar-day').forEach(d => {
                    d.classList.remove('selected');
                });
                this.classList.add('selected');
                
                calendarPopup.classList.remove('active');
            });

            calendarDays.appendChild(dayElement);
        }
    }

    renderCalendar();
</script>
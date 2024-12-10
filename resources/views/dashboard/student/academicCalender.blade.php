<x-app-layout>
    <x-slot name="header">
        @include('dashboard.partials.header')
    </x-slot>

    <div class="container mx-auto px-4 pb-4">
        <div class="w-auto mx-auto">
            <!-- Header -->
            {{-- <div class="text-2xl font-bold text-[#202224] mb-4">Academic Calendar</div> --}}

            <!-- Main Content Area -->
            <div class="flex-1 flex-col bg-white rounded-lg shadow-lg p-6 w-auto h-auto">
                <!-- Calendar Header -->
                <div class="flex justify-between items-center pb-2 mb-4">
                    <div class="flex items-center w-full justify-between">
                        <div class="flex">
                            <button id="prev-btn" class="text-black px-3 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8">
                                    <path d="M10.8284 12.0007L15.7782 16.9504L14.364 18.3646L8 12.0007L14.364 5.63672L15.7782 7.05093L10.8284 12.0007Z"></path>
                                </svg>
                            </button>

                            <div class="text-xl font-bold text-black text-center" id="calendar-title"></div>

                            <button id="next-btn" class="text-black px-3 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8">
                                    <path d="M13.1717 12.0007L8.22192 7.05093L9.63614 5.63672L16.0001 12.0007L9.63614 18.3646L8.22192 16.9504L13.1717 12.0007Z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 mt-2">
                        <button class="calendar-view-btn text-lg font-bold py-1 px-2 rounded-lg w-full text-center" id="day-view">Day</button>
                        <button class="calendar-view-btn text-lg font-bold py-1 px-2 rounded-lg w-full text-center" id="week-view">Week</button>
                        <button class="calendar-view-btn text-lg font-bold py-1 px-2 rounded-lg w-full text-center" id="month-view">Month</button>
                    </div>
                </div>

                <!-- FullCalendar will be injected here -->
                <div id="calendar" class="bg-white rounded-lg shadow-md h-[600px] w-full"></div>
            </div>
        </div>

        <!-- Add FullCalendar JS and CSS -->
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet" />
        <script>
            document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: false,
                events: 'calendar/events', 

                eventContent: function() {
                    return { domNodes: [] };
                },

                eventsSet: function() {
                    document.querySelectorAll('.fc-event').forEach(function(event) {
                        event.classList.add('border-0', 'border-white', 'bg-transparent', 'text-transparent');
                    });
                    updateEventDots();  
                },

                datesSet: function() {
                    updateEventDots();  
                },

                dateClick: function(info) {
                    console.log('Tanggal yang diklik: ' + info.dateStr);
                },

                datesSet: function() {
                    updateCalendarTitle(calendar.view.title);
                }
            });

            calendar.render();

            function setActiveButton(activeButton) {
                const buttons = document.querySelectorAll('.calendar-view-btn');
                buttons.forEach(button => {
                    button.classList.remove('bg-blue-500', 'text-white');
                    button.classList.add('text-sm', 'font-semibold', 'py-1', 'px-3', 'rounded-lg', 'text-black');
                });

                activeButton.classList.add('bg-blue-500', 'text-white', 'px-3', 'py-2', 'rounded-lg');
            }

            function updateCalendarTitle(title) {
                document.getElementById('calendar-title').innerText = title;
            }

            document.getElementById('day-view').addEventListener('click', function() {
                calendar.changeView('timeGridDay');
                setActiveButton(this);
                document.querySelectorAll('.fc-event').forEach(function(event) {
                        event.classList.add('border-0', 'border-white', 'bg-transparent', 'text-transparent');
                    });
            });

            document.getElementById('week-view').addEventListener('click', function() {
                calendar.changeView('timeGridWeek');
                setActiveButton(this);
                document.querySelectorAll('.fc-event').forEach(function(event) {
                        event.classList.add('border-0', 'border-white', 'bg-transparent', 'text-transparent');
                    });
            });

            document.getElementById('month-view').addEventListener('click', function() {
                calendar.changeView('dayGridMonth');
                setActiveButton(this);
                document.querySelectorAll('.fc-event').forEach(function(event) {
                        event.classList.add('border-0', 'border-white', 'bg-transparent', 'text-transparent');
                    });
            });

            setActiveButton(document.getElementById('month-view'));

            document.getElementById('prev-btn').addEventListener('click', function() {
                calendar.prev();
                document.querySelectorAll('.fc-event').forEach(function(event) {
                        event.classList.add('border-0', 'border-white', 'bg-transparent', 'text-transparent');
                    });
            });

            document.getElementById('next-btn').addEventListener('click', function() {
                calendar.next();
                document.querySelectorAll('.fc-event').forEach(function(event) {
                        event.classList.add('border-0', 'border-white', 'bg-transparent', 'text-transparent');
                    });
            });

            function updateEventDots() {
                document.querySelectorAll('.events-dot').forEach(el => el.remove());

                calendar.getEvents().forEach(event => {
                    let eventDateStr = event.startStr;
                    let dayCell = document.querySelector(`[data-date="${eventDateStr}"] .fc-daygrid-day-frame`);

                    if (dayCell) {
                        let existingDots = dayCell.querySelectorAll('.events-dot');
                        let leftOffset = existingDots.length * 15;

                        let dot = document.createElement('div');
                        dot.classList.add(
                            'events-dot',
                            'absolute',
                            'bottom-1',
                            'left-1',
                            'w-3',
                            'h-3',
                            'rounded-full'
                        );

                        let eventColor = event.backgroundColor || 'orange';
                        dot.style.backgroundColor = eventColor;

                        dot.style.left = `${1 + leftOffset}px`;
                        dayCell.appendChild(dot);
                        dayCell.style.border = 'none';

                        dot.addEventListener('mouseover', function() {
                            console.log(event);
                            let allDots = document.querySelectorAll('.events-dot');
                            allDots.forEach(d => {
                                if (d.style.backgroundColor === dot.style.backgroundColor) {
                                    const dotColor = d.style.backgroundColor;
                                    let rgbaColor;
                                    
                                    if (dotColor.startsWith('rgb')) {
                                        rgbaColor = dotColor.replace('rgb', 'rgba').replace(')', ', 0.5)').replace('rgb(', 'rgba(');
                                    } else if (dotColor.startsWith('#')) {
                                        const r = parseInt(dotColor.slice(1, 3), 16);
                                        const g = parseInt(dotColor.slice(3, 5), 16);
                                        const b = parseInt(dotColor.slice(5, 7), 16);
                                        rgbaColor = `rgba(${r}, ${g}, ${b}, 0.5)`;
                                    }

                                    d.parentElement.style.backgroundColor = rgbaColor;
                                }
                            });
                            let tooltip = document.createElement('div');
                            tooltip.classList.add('event-tooltip');
                            tooltip.style.position = 'absolute';
                            tooltip.style.maxWidth = '250px';
                            tooltip.style.maxHeight = '150px'; 
                            tooltip.style.backgroundColor = '#333';
                            tooltip.style.color = '#fff';
                            tooltip.style.borderRadius = '4px';
                            tooltip.style.padding = '5px';
                            tooltip.style.fontSize = '12px';
                            tooltip.style.zIndex = '1000';

                            tooltip.style.whiteSpace = 'normal'; 
                            tooltip.style.overflow = 'hidden'; 
                            tooltip.style.textOverflow = 'ellipsis'; 
                            tooltip.style.lineHeight = '1.5'; 

                            tooltip.innerHTML = `                                        
                                <strong> ${event.extendedProps.startDate} -  ${event.extendedProps.endDate} : ${event.title} </strong> <br>
                                `;
                                
                                document.body.appendChild(tooltip);
                                
                            // <strong>Title:</strong> ${event.title} <br>
                            // <strong>Description:</strong> ${event.extendedProps.description || 'No description available'} <br>
                            // <strong>Location:</strong> ${event.extendedProps.location || 'No location available'}
                            
                            const dotRect = dot.getBoundingClientRect();
                            tooltip.style.left = `${dotRect.left + dotRect.width / 2 - tooltip.offsetWidth / 2}px`;
                            tooltip.style.top = `${dotRect.top - tooltip.offsetHeight - 5}px`; 
                        });

                        dot.addEventListener('mouseout', function() {
                            let allCells = document.querySelectorAll('.fc-daygrid-day-frame');
                            allCells.forEach(cell => {
                                cell.style.backgroundColor = ''; 
                            });

                            let tooltip = document.querySelector('.event-tooltip');
                            if (tooltip) {
                                tooltip.remove();
                            }
                        });
                    }
                });
            }
        });
    </script>

</x-app-layout>

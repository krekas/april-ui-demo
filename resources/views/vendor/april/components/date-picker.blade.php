@props([
'open' => false,
'value' => null,
'mode' => 'single',
'format' => 'MM/dd/yyyy',
'name' => '',
'required' => false,
'fromName',
'toName',
'showPresets' => true,
])
@php
$hiddenInputAttributes = new \Illuminate\View\ComponentAttributeBag;
$fromInputName = $fromName ?? ($name !== '' ? $name.'[from]' : null);
$toInputName = $toName ?? ($name !== '' ? $name.'[to]' : null);
$calendarValue = $value;

if ($mode === 'range' && data_get($value, 'from') === data_get($value, 'to')) {
    $calendarValue = ['from' => data_get($value, 'from'), 'to' => null];
}
@endphp
<div data-slot="date-picker" data-state="{{$open ? 'open' : 'closed'}}" x-ref="datePickerInput" x-modelable="value"
    x-data="{
        ...datePicker(@js($open), @js($value), @js($mode), @js($format)),
        ignoreInitialCalendarChange: @js($value !== null),
        formatInputDate(date) {
            if (!date) return '';

            const value = new Date(date);
            const year = value.getFullYear();
            const month = String(value.getMonth() + 1).padStart(2, '0');
            const day = String(value.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        },
        applyPreset(preset) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const from = new Date(today);

            if (preset === 'last-7-days') from.setDate(from.getDate() - 6);
            if (preset === 'this-month') from.setDate(1);

            this.value = { from, to: today };
            this.applyRange();
        },
        applyRange() {
            if (!this.value?.from || !this.value?.to) return;

            this.$root.querySelector('[data-range-boundary=from]').value = this.formatInputDate(this.value.from);
            this.$root.querySelector('[data-range-boundary=to]').value = this.formatInputDate(this.value.to);
            this.closePicker();
            this.$dispatch('date-range-apply', { value: this.value });
        },
        handleCalendarChange(value) {
            if (this.ignoreInitialCalendarChange) {
                this.ignoreInitialCalendarChange = false;

                return;
            }

            this.value = value;
        },
    }" x-bind="root" {{$attributes->
    except('x-teleport')->class(['relative'])}}>
    <april:button class="justify-start min-h-10 w-full h-fit text-left flex-wrap font-normal" type="button"
        x-bind="trigger" variant="outline">
        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <template x-if="mode != 'multiple' && mode != 'range'">
            <span>
                <span x-text="value == null ? 'Pick a date' : formatDate(value)"></span>
                <input type="hidden" {{$hiddenInputAttributes}} @if ($name !== '') name="{{$name}}" @endif :value="value" />
            </span>
        </template>
        <template x-if="mode == 'multiple'">
            <span>
                <template x-if="!value">
                    <span>Select date(s)</span>
                </template>
                <template x-if="value">
                    <template x-for="(item, index) in value">
                        <span>
                            <span x-text="formatDate(item)" class="text-sm border-border mr-1"></span>
                            <input type="hidden" {{$hiddenInputAttributes}} @if ($name !== '') name="{{$name}}" @endif
                                :value="(new Date(item))?.toISOString().split('T')[0]" />
                        </span>
                    </template>
                </template>
            </span>
        </template>
        <template x-if="mode == 'range'">
            <span class="min-h-5">
                <template x-if="!value?.from && !value?.to">
                    <span>Pick a range of dates</span>
                </template>
                <template x-if="value?.from">
                    <span x-text="formatDate(value.from)"></span>
                </template>
                <template x-if="value?.to && formatInputDate(value.from) !== formatInputDate(value.to)">
                    <span x-text="' – ' + formatDate(value.to)"></span>
                </template>
                <input type="hidden" data-range-boundary="from" value="{{ data_get($value, 'from') }}" @if ($fromInputName !== null) name="{{$fromInputName}}" @endif {{$hiddenInputAttributes}} />
                <input type="hidden" data-range-boundary="to" value="{{ data_get($value, 'to') }}" @if ($toInputName !== null) name="{{$toInputName}}" @endif {{$hiddenInputAttributes}} />
            </span>
        </template>
    </april:button>
    @if ($attributes->has('x-teleport'))
    <template x-teleport="{{$attributes->get('x-teleport')}}">
    @endif
    <div x-bind="calendar" @change.stop="handleCalendarChange($event.detail.value)" x-cloak class="absolute left-0 top-full z-10 mt-3 w-[19rem] max-w-full rounded-lg border border-input bg-background p-2 shadow-lg">
        @if ($showPresets && $mode === 'range')
            <div class="grid grid-cols-3 gap-1 border-b border-border p-1 pb-3" aria-label="Date range presets">
                <april:button type="button" variant="ghost" size="sm" class="px-2" @click="applyPreset('today')">Today</april:button>
                <april:button type="button" variant="ghost" size="sm" class="px-2" @click="applyPreset('last-7-days')">Last 7 days</april:button>
                <april:button type="button" variant="ghost" size="sm" class="px-2" @click="applyPreset('this-month')">This month</april:button>
            </div>
        @endif
        <april:calendar :required="$required" :mode="$mode" :selected="$calendarValue"
            tabindex="0" class="border-0 px-2 shadow-none outline-none" />
        @if ($mode === 'range')
            <div class="flex justify-end border-t border-border p-1 pt-3">
                <april:button type="button" size="sm" x-bind:disabled="!value?.from || !value?.to" @click="applyRange()">Apply</april:button>
            </div>
        @endif
    </div>
    @if ($attributes->has('x-teleport'))
    </template>
    @endif
</div>

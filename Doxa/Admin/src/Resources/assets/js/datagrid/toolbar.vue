<template>
    <div class="flex items-center justify-between gap-4 mt-7 max-md:flex-wrap">

        <!-- Left Toolbar -->
        <div class="flex gap-x-1">

            <!-- Channel & Locale Selector -->
            <chlo v-if="$parent.meta.has_variations"
                :_channels="$parent.channels"
                :_current_channel_id="$parent.applied.current_channel_id"
                :_current_locale_id="$parent.applied.current_locale_id"
                :callback="$parent.applyChanelAndLocale"
            />

            <!-- Mass Actions Panel -->
            <div
                class="flex items-center w-full gap-x-1"
                v-if="$parent.applied.massActions.indices.length"
            >
                <dropdown>

                    <template #toggle>
                        <button
                            type="button"
                            class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 focus:ring-black dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                        >
                            <span>
                                {{ vocab['toolbar.mass-actions.select-action'] }}
                            </span>

                            <span class="text-2xl icon-sort-down"></span>
                        </button>
                    </template>

                    <template #menu>
                        <div class="!p-0 shadow-[0_5px_20px_rgba(0,0,0,0.15)] dark:border-gray-800">
                            <template v-for="massAction in $parent.available.massActions">
                                <li
                                    class="relative overflow-visible group/item"
                                    v-if="massAction?.options?.length"
                                >
                                    <a
                                        class="whitespace-no-wrap flex cursor-not-allowed justify-between gap-1.5 rounded-t px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                                        href="javascript:void(0);"
                                    >
                                        <i
                                            class="text-2xl"
                                            :class="massAction.icon"
                                            v-if="massAction?.icon"
                                        >
                                        </i>

                                        <span>
                                            {{ massAction.title }}
                                        </span>

                                        <i class="-mt-px text-xl icon-arrow-left"></i>
                                    </a>

                                    <ul class="absolute top-0 z-10 hidden w-max min-w-[150px] rounded border bg-white shadow-[0_5px_20px_rgba(0,0,0,0.15)] group-hover/item:block dark:border-gray-800 dark:bg-gray-900 ltr:left-full rtl:right-full">
                                        <li v-for="option in massAction.options">
                                            <a
                                                class="block px-4 py-2 text-sm text-gray-600 whitespace-no-wrap rounded-t hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                                                href="javascript:void(0);"
                                                v-text="option.label"
                                                @click="$parent.performMassAction(massAction, option)"
                                            >
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                <li v-else>
                                    <a
                                        class="whitespace-no-wrap flex gap-1.5 rounded-b px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                                        href="javascript:void(0);"
                                        @click="$parent.performMassAction(massAction)"
                                    >
                                        <i
                                            class="text-2xl"
                                            :class="massAction.icon"
                                            v-if="massAction?.icon"
                                        >
                                        </i>

                                        {{ massAction.title }}
                                    </a>
                                </li>
                            </template>
                        </div>
                    </template>

                </dropdown>

            </div>

            <!-- Search Panel -->
            <div class="flex items-center w-full gap-x-1" v-else>
                <!-- Search Panel -->
                <div class="flex max-w-[445px] items-center max-sm:w-full max-sm:max-w-full">
                    <div class="relative w-full">
                        <input
                            type="text"
                            name="search"
                            :value="getAppliedColumnValues('all')"
                            class="block w-full rounded-lg border bg-white py-1.5 leading-6 text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400 ltr:pl-3 ltr:pr-10 rtl:pl-10 rtl:pr-3"
                            :placeholder="vocab['toolbar.search.title']"
                            autocomplete="off"
                            @keyup.enter="$parent.filterPage"
                        >

                        <div class="icon-search pointer-events-none absolute top-2 flex items-center text-2xl ltr:right-2.5 rtl:left-2.5">
                        </div>
                    </div>
                </div>

                <!-- Information Panel -->
                <div class="ltr:pl-2.5 rtl:pr-2.5">
                    <p class="text-sm font-light text-gray-800 dark:text-white">
                        {{ vocab['toolbar.results'].replace(':total', $parent.meta.paginator.total) }}
                    </p>
                </div>
            </div>
            
        </div>

        <!-- Right Toolbar -->
        <div class="flex gap-x-4">

            <!-- Filter Panel -->
            <Drawer width="350px" ref="filterDrawer" v-if="$parent.available.columns.length">

                <template #toggle>
                    <div>
                        <div
                            class="relative inline-flex w-full max-w-max cursor-pointer select-none appearance-none items-center justify-between gap-x-1 rounded-md border bg-white px-1 py-1.5 text-center text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:outline-none focus:ring-2 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 ltr:pl-3 ltr:pr-5 rtl:pl-5 rtl:pr-3"
                            :class="{'[&>*]:text-blue-600 [&>*]:dark:text-white': $parent.applied.filters.columns.length > 1}"
                        >
                            <span class="text-2xl icon-filter"></span>

                            <span>
                                {{ vocab['toolbar.filters.title'] }}
                            </span>

                            <span
                                class="icon-dot absolute right-2 top-1.5 text-sm font-bold"
                                v-if="$parent.applied.filters.columns.length > 1"
                            ></span>
                        </div>

                        <div class="z-10 hidden w-full bg-white divide-y divide-gray-100 rounded shadow dark:bg-gray-900">
                        </div>
                    </div>
                </template>

                <!-- Drawer Header -->
                <template #header>
                    <div class="flex items-center justify-between p-3">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            {{ vocab['filters.title'] }}
                        </p>
                    </div>
                </template>

                <!-- Drawer Content -->
                <template #content>
                    <div class="flex-1 p-5 overflow-auto max-sm:px-4">
                        <div v-for="column in $parent.available.columns">
                            <div v-if="column.filterable">
                                
                                <!-- Boolean -->
                                <div v-if="column.type === 'boolean' || column.type === 'bool'">

                                    <div class="flex items-center justify-between">
                                        <p
                                            class="text-sm font-medium leading-6 text-gray-800 dark:text-white"
                                            v-text="column.label"
                                        >
                                        </p>

                                        <div
                                            class="flex items-center gap-x-1.5"
                                            @click="removeAppliedColumnAllValues(column.index)"
                                        >
                                            <p
                                                class="text-xs font-medium leading-6 text-blue-600 cursor-pointer"
                                                v-if="hasAnyAppliedColumnValues(column.index)"
                                            >
                                                {{ vocab['filters.custom-filters.clear-all'] }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mb-2 mt-1.5">
                                        <dropdown>

                                            <template #toggle>
                                                <button
                                                    type="button"
                                                    class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                                >
                                                    <span 
                                                        class="text-sm text-gray-400 dark:text-gray-400" 
                                                        v-text="vocab['filters.select']"
                                                    >
                                                    </span>

                                                    <span class="text-2xl icon-sort-down"></span>
                                                </button>
                                            </template>
                            
                                            <template #menu>
                                                <template v-for="option in column.options">
                                                    <li 
                                                        class="px-5 py-2 text-sm text-gray-600 cursor-pointer hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                                                        v-text="option.label"
                                                        @click="$parent.filterPage(option.value, column)" 
                                                    />
                                                </template>
                                            </template>

                                        </dropdown>

                                    </div>

                                    <div class="flex flex-wrap gap-2 mb-4">
                                        <p
                                            class="flex items-center px-2 py-1 font-semibold text-white bg-gray-600 rounded"
                                            v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                                        >
                                            <!-- Retrieving the label from the options based on the applied column value. -->
                                            <span v-text="column.options.find((option => option.value == appliedColumnValue)).label"></span>

                                            <span
                                                class="icon-cross cursor-pointer text-lg text-white ltr:ml-1.5 rtl:mr-1.5"
                                                @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                                            >
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <!-- related -->
                                <div v-else-if="column.type === 'related'">
                                    <!-- Basic -->
                                    <div v-if="column.options.type === 'basic'">
                                        <div class="flex items-center justify-between">
                                            <p
                                                class="text-sm font-medium leading-6 text-gray-800 dark:text-white"
                                                v-text="column.label"
                                            >
                                            </p>

                                            <div
                                                class="flex items-center gap-x-1.5"
                                                @click="removeAppliedColumnAllValues(column.index)"
                                            >
                                                <p
                                                    class="text-xs font-medium leading-6 text-blue-600 cursor-pointer"
                                                    v-if="hasAnyAppliedColumnValues(column.index)"
                                                >
                                                    {{ vocab['filters.custom-filters.clear-all'] }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mb-2 mt-1.5">
                                            <dropdown>
                                                <template #toggle>
                                                    <button
                                                        type="button"
                                                        class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                                    >
                                                        <span 
                                                            class="text-sm text-gray-400 dark:text-gray-400" 
                                                            v-text="vocab['filters.select']"
                                                        >
                                                        </span>

                                                        <span class="text-2xl icon-sort-down"></span>
                                                    </button>
                                                </template>

                                                <template #menu>
                                                    <template v-for="option in column.options.params.options">
                                                        <li 
                                                            class="px-5 py-2 text-sm text-gray-600 cursor-pointer hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                                                            v-text="option.title"
                                                            @click="$parent.filterPage(option.id, column)" 
                                                        />
                                                    </template>
                                                </template>

                                            </dropdown>    

                                        </div>

                                        <div class="flex flex-wrap gap-2 mb-4">
                                            <p
                                                class="flex items-center px-2 py-1 font-semibold text-white bg-gray-600 rounded"
                                                v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                                            >
                                                <!-- Retrieving the label from the options based on the applied column value. -->
                                                <span v-text="column.options.params.options.find((option => option.id == appliedColumnValue)).title"></span>

                                                <span
                                                    class="icon-cross cursor-pointer text-lg text-white ltr:ml-1.5 rtl:mr-1.5"
                                                    @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                                                >
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                </div>

                                <!-- Dropdown -->
                                <div v-else-if="column.type === 'dropdown'">
                                    <!-- Basic -->
                                    <div v-if="column.options.type === 'basic'">
                                        <div class="flex items-center justify-between">
                                            <p
                                                class="text-sm font-medium leading-6 text-gray-800 dark:text-white"
                                                v-text="column.label"
                                            >
                                            </p>

                                            <div
                                                class="flex items-center gap-x-1.5"
                                                @click="removeAppliedColumnAllValues(column.index)"
                                            >
                                                <p
                                                    class="text-xs font-medium leading-6 text-blue-600 cursor-pointer"
                                                    v-if="hasAnyAppliedColumnValues(column.index)"
                                                >
                                                    {{ vocab['filters.custom-filters.clear-all'] }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mb-2 mt-1.5">
                                            <dropdown>
                                                <template #toggle>
                                                    <button
                                                        type="button"
                                                        class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                                    >
                                                        <span 
                                                            class="text-sm text-gray-400 dark:text-gray-400" 
                                                            v-text="vocab['filters.select']"
                                                        >
                                                        </span>

                                                        <span class="text-2xl icon-sort-down"></span>
                                                    </button>
                                                </template>

                                                <template #menu>
                                                    <template v-for="option in column.options.params.options">
                                                        <li 
                                                            class="px-5 py-2 text-sm text-gray-600 cursor-pointer hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950"
                                                            v-text="option.label"
                                                            @click="$parent.filterPage(option.value, column)" 
                                                        />
                                                    </template>
                                                </template>

                                            </dropdown>    

                                        </div>

                                        <div class="flex flex-wrap gap-2 mb-4">
                                            <p
                                                class="flex items-center px-2 py-1 font-semibold text-white bg-gray-600 rounded"
                                                v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                                            >
                                                <!-- Retrieving the label from the options based on the applied column value. -->
                                                <span v-text="column.options.params.options.find((option => option.value == appliedColumnValue)).label"></span>

                                                <span
                                                    class="icon-cross cursor-pointer text-lg text-white ltr:ml-1.5 rtl:mr-1.5"
                                                    @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                                                >
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                </div>

                                <!-- Date Range -->
                                <div v-else-if="column.type === 'date_range'">
                                    <div class="flex items-center justify-between">
                                        <p
                                            class="text-sm font-medium leading-6 dark:text-white"
                                            v-text="column.label"
                                        >
                                        </p>

                                        <div
                                            class="flex items-center gap-x-1.5"
                                            @click="removeAppliedColumnAllValues(column.index)"
                                        >
                                            <p
                                                class="text-xs font-medium leading-6 text-blue-600 cursor-pointer"
                                                v-if="hasAnyAppliedColumnValues(column.index)"
                                            >
                                                {{ vocab['filters.custom-filters.clear-all'] }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-1.5 grid grid-cols-2 gap-1.5">
                                        <p
                                            class="px-3 py-2 text-sm font-medium leading-6 text-center text-gray-600 transition-all border rounded-md cursor-pointer hover:border-gray-400 dark:border-gray-800 dark:text-gray-300 dark:hover:border-gray-400"
                                            v-for="option in column.options"
                                            v-text="option.label"
                                            @click="$parent.filterPage(
                                                $event,
                                                column,
                                                { quickFilter: { isActive: true, selectedFilter: option } }
                                            )"
                                        >
                                        </p>

                                        <datepicker ::allow-input="false">
                                            <input
                                                value=""
                                                class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                                                :type="column.input_type"
                                                :name="`${column.index}[from]`"
                                                placeholder="From"
                                                :ref="`${column.index}[from]`"
                                                @change="$parent.filterPage(
                                                    $event,
                                                    column,
                                                    { range: { name: 'from' }, quickFilter: { isActive: false } }
                                                )"
                                            />
                                        </datepicker>

                                        <datepicker ::allow-input="false">
                                            <input
                                                value=""
                                                class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                                                :type="column.input_type"
                                                :name="`${column.index}[to]`"
                                                placeholder="To"
                                                :ref="`${column.index}[to]`"
                                                @change="$parent.filterPage(
                                                    $event,
                                                    column,
                                                    { range: { name: 'to' }, quickFilter: { isActive: false } }
                                                )"
                                            />
                                        </datepicker>

                                        <div class="flex flex-wrap gap-2 mb-4">
                                            <p
                                                class="flex items-center px-2 py-1 font-semibold text-white bg-gray-600 rounded"
                                                v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                                            >
                                                <span v-text="appliedColumnValue.join(' to ')"></span>

                                                <span
                                                    class="icon-cross cursor-pointer text-lg text-white ltr:ml-1.5 rtl:mr-1.5"
                                                    @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                                                >
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Any -->
                                <div v-else>
                                    <div class="flex items-center justify-between">
                                        <p
                                            class="text-sm font-medium leading-6 dark:text-white"
                                            v-text="column.label"
                                        >
                                        </p>
                        
                                        <div
                                            class="flex items-center gap-x-1.5"
                                            @click="removeAppliedColumnAllValues(column.index)"
                                        >
                                            <p
                                                class="text-xs font-medium leading-6 text-blue-600 cursor-pointer"
                                                v-if="hasAnyAppliedColumnValues(column.index)"
                                            >
                                                {{ vocab['filters.custom-filters.clear-all'] }}
                                            </p>
                                        </div>
                                    </div>
                        
                                    <div class="mb-2 mt-1.5 grid">
                                        <input
                                            type="text"
                                            class="w-full form-input"
                                            :name="column.index"
                                            :placeholder="column.label"
                                            @keyup.enter="$parent.filterPage($event, column)"
                                        />
                                    </div>
                        
                                    <div class="flex flex-wrap gap-2 mb-4">
                                        <p
                                            class="flex items-center px-2 py-1 font-semibold text-white bg-gray-600 rounded"
                                            v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                                        >
                                            <span v-text="appliedColumnValue"></span>
                        
                                            <span
                                                class="icon-cross cursor-pointer text-lg text-white ltr:ml-1.5 rtl:mr-1.5"
                                                @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                                            >
                                            </span>
                                        </p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </template>

            </Drawer>

            <!-- Pagination -->
            <div class="flex items-center gap-x-2" v-if="(!$parent.meta.disable_pagination && $parent.applied.pagination.per_page)">

                <dropdown>
                    <template #toggle>
                        <button
                            type="button"
                            class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                        >
                            <span v-text="$parent.applied.pagination.per_page"></span>
                            <span class="text-2xl icon-sort-down"></span>
                        </button>
                    </template>
    
                    <template #menu>
                        <template v-for="per_page_option in $parent.meta.paginator.per_page_options">
                            <div 
                                v-text="per_page_option"
                                class="px-5 py-2 text-sm cursor-pointer"
                                :class="per_page_option == $parent.applied.pagination.per_page ? 
                                    'text-gray-400 dark:text-gray-500'
                                    : 
                                    'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-950'"
                                @click="$parent.changePerPageOption(per_page_option)"    
                            />
                        </template>
                    </template>
                </dropdown>

                <p class="text-gray-600 whitespace-nowrap dark:text-gray-300 max-sm:hidden">
                    {{ vocab['toolbar.per-page'] }}
                </p>

                <input
                    type="text"
                    class="inline-flex min-h-[38px] max-w-[40px] appearance-none items-center justify-center gap-x-1 rounded-md border bg-white px-3 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400 max-sm:hidden"
                    :value="$parent.applied.pagination.current_page"
                    @change="$parent.changePage(parseInt($event.target.value))"
                >

                <div class="text-gray-600 whitespace-nowrap dark:text-gray-300">
                    <span> 
                        {{ vocab['toolbar.of'].replace(':total', $parent.meta.paginator.last_page) }}
                    </span>
                </div>

                <!-- Pagination -->
                <div class="flex items-center gap-1">
                    <div
                        class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 transition-all marker:shadow hover:bg-gray-200 active:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-800"
                        @click="$parent.changePage('previous')"
                    >
                        <span class="text-2xl icon-sort-left"></span>
                    </div>

                    <div
                        class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 transition-all marker:shadow hover:bg-gray-200 active:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-800"
                        @click="$parent.changePage('next')"
                    >
                        <span class="text-2xl icon-sort-right"></span>
                    </div>
                </div>
            </div>

        </div>

    </div>
</template>

<script>

import Drawer from "../components/drawer.vue";
// import Chlo from "../tools/chlo.vue";
import dropdown from "../components/dropdown.vue";
// import datepicker from "../datepicker/date.vue";

export default {
    inject: ['vocab'],   
    components: {
        Drawer,
        //Chlo,
        dropdown,
        // datepicker,
    },
    methods: {
        removeAppliedColumnAllValues(columnIndex) {
            this.$parent.applied.filters.columns = this.$parent.applied.filters.columns.filter(column => column.index !==
                columnIndex);
            this.$parent.get();
        },
        removeAppliedColumnValue(columnIndex, appliedColumnValue) {
            let appliedColumn = this.$parent.findAppliedColumn(columnIndex);
            appliedColumn.value = appliedColumn?.value.filter(value => value !== appliedColumnValue);

            /**
             * Clean up is done here. If there are no applied values present, there is no point in including the applied column as well.
             */
            if (!appliedColumn.value.length) {
                this.$parent.applied.filters.columns = this.$parent.applied.filters.columns.filter(column => column
                    .index !== columnIndex);
            }
            this.$parent.get();
        },
        hasAnyAppliedColumnValues(columnIndex) {
            let appliedColumn = this.$parent.findAppliedColumn(columnIndex);
            return appliedColumn?.value.length > 0;
        },
        getAppliedColumnValues(columnIndex) {
            let appliedColumn = this.$parent.findAppliedColumn(columnIndex);
            return appliedColumn?.value ?? [];
        },
    },
};

</script>


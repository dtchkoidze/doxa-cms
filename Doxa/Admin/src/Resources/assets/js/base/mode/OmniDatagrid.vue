<template>

    <!-- <Flash ref="flash" /> -->
    <ConfirmModal ref="confirm_modal" />

    <template v-if="loading">
        loading
    </template>

    <template v-else>

        <div class="mb-4 sm:flex sm:justify-between sm:items-center">
            <!-- Left: Title -->
            <div class="mb-2 sm:mb-0">
                <h1 class="text-2xl font-bold text-gray-800 md:text-3xl dark:text-gray-100">{{ vocab.title }}</h1>
            </div>

            <!-- Right: Actions  -->
            <div class="grid justify-start grid-flow-col gap-2 sm:auto-cols-max sm:justify-end">
                <!-- Add button -->
                <button v-if="permissions.create"
                    class="text-gray-100 bg-gray-900 btn hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white"
                    @click="newRecord()">
                    <svg class="fill-current shrink-0 xs:hidden" width="16" height="16" viewBox="0 0 16 16">
                        <path
                            d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
                    </svg>
                    <span class="max-xs:sr-only">{{ vocab["create-button"] }}</span>
                </button>
            </div>
        </div>

        <!-- Page header -->
        <div class="mb-4 sm:flex sm:justify-between sm:items-center">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <chlo v-if="meta.has_variations" :_channels="channels" :_current_channel_id="applied.current_channel_id"
                    :_current_locale_id="applied.current_locale_id" :callback="applyChanelAndLocale" />
            </div>

            <!-- Right: Actions  -->
            <div class="grid justify-start grid-flow-col gap-2 sm:auto-cols-max sm:justify-end">

                <mass-actions v-if="applied.massActions.indices.length" :mass_actions="available.massActions" />

                <div class="flex max-w-[445px] items-center max-sm:w-full max-sm:max-w-full">
                    <div class="relative w-full">
                        <input type="text" name="search" :value="getAppliedColumnValues('all')"
                            class="w-full form-input" :placeholder="vocab['toolbar.search.title']" autocomplete="off"
                            @keyup.enter="filterPage">
                        <i
                            class=" fa-solid fa-magnifying-glass pointer-events-none absolute top-3 flex items-center ltr:right-2.5 rtl:left-2.5"></i>
                    </div>
                </div>

                <filters />

            </div>

        </div>

        <!-- <datagrid-header /> -->

        <!-- <datagrid-toolbar /> -->

        <datagrid-table />

        <div class="flex items-center justify-between mt-8">
            <div></div>
            <datagrid-paginator v-if="!meta.disable_pagination && applied.paginator.per_page" :parent="this" />
            <DatagridPerPage v-if="!meta.disable_pagination" :options="applied.paginator.per_page_options"
                :per_page="applied.pagination.per_page" />
        </div>


    </template>

</template>

<script>

import { computed } from 'vue'

// import Flash from "./flash/flash.vue";
import ConfirmModal from "../../components/ConfirmModal.vue";

// import DatagridHeader from "../datagrid/header.vue"
// import DatagridToolbar from "../datagrid/toolbar.vue"
import DatagridTable from "../../datagrid/table.vue";
import DatagridPaginator from "../../components/pagination/paginator.vue";
import DatagridPerPage from "../../components/pagination/per-page.vue";

import Filters from "../../datagrid/filters.vue";
import Chlo from "../../components/chlo-alt.vue";
import MassActions from "../../datagrid/mass-actions.vue";

export default {
    components: {
        // Flash,
        ConfirmModal,

        //DatagridHeader,
        //DatagridToolbar,
        DatagridTable,

        DatagridPaginator,
        DatagridPerPage,

        Filters,
        Chlo,
        MassActions,
    },
    data() {
        return {
            loading: true,
            meta: Object,
            vocab: Object,
            routes: Object,
            permissions: Object,
            channels: Object,
            records: [],
            applied: {
                filters: {
                    columns: [{
                        index: 'all',
                        value: [],
                    },],
                },
                pagination: {
                    current_page: null,
                    per_page: null,
                },
                sort: {
                    column: null,
                    order: null,
                },
                current_channel_id: null,
                current_locale_id: null,
                massActions: {
                    meta: {
                        mode: 'none',

                        action: null,
                    },

                    indices: [],

                    value: null,
                },
            },
            available: {
                id: null,
                columns: [],
                actions: [],
                massActions: [],
            },
            init: true,
        }
    },
    props: {
        module: String,
    },

    provide() {
        return {
            vocab: computed(() => this.vocab),
        }
    },
    mounted() {
        //console.log("Omnidatagrid.vue mounted");
    },
    updated() {
        //console.log("Omnidatagrid.vue updated");
    },
    created() {
        //console.log("Omnidatagrid.vue created");
        this.boot();
    },
    updated() {
        //console.log('updated');
        this.update();
    },
    methods: {
        boot() {
            let datagrids = this.getDatagrids();

            if (datagrids?.length) {
                const currentDatagrid = datagrids.find(({
                    module
                }) => module === this.module);
                if (currentDatagrid) {
                    this.applied = Object.assign(this.applied, currentDatagrid.applied);
                    this.get();
                    return;
                }
            }

            this.get();
        },

        update() {
            //console.log('update');
            // if (this.meta.sortable_by_position && this.applied.sort?.column.includes('position')) {
            //     console.log('-----sortable--------');
            //     //if(this.meta.sortable_by_position && (this.applied.sort && this.applied.sort.column.includes('position'))){
            //     var el = document.getElementById('sortableList');
            //     if (el) {
            //         console.log('el: ', el);
            //         let obj = this;
            //         var sortable = Sortable.create(el, {
            //             handle: '.icon-drag',
            //             onEnd: function (evt) {
            //                 //console.log('evt:', evt);
            //                 obj.onRowsSortDragEnd();
            //             },
            //         });

            //         console.log('sortable: ', sortable);
            //     }
            // }
        },

        newRecord() {
            this.$emitter.emit("newRecord", {
                route: this.routes.create,
                use_route: false,
            });
        },

        get(extraParams = {}) {

            //console.log('get() this.applied: ', this.applied);

            let params = {
                filters: {},
            };

            const param_fields = [
                'current_channel_id',
                'current_locale_id',
                'pagination',
                'sort',
                //'filters',
            ];


            //console.log('current_channel_id: ', this.applied.current_channel_id);
            //console.log('current_locale_id: ', this.applied['current_locale_id']);

            for (var field in this.applied) {
                //console.log('field: ', field, 'val: ',this.applied[field]);
                if (param_fields.includes(field)) {
                    //console.log('includes');
                    params[field] = this.applied[field];
                } else {
                    //console.log('NOT includes');
                }
            }

            //params.pagination


            this.applied.filters.columns.forEach(column => {
                params.filters[column.index] = column.value;
            });

            params.init = this.init;

            axios.get('/admin/' + this.module + '/get_datagrid', {
                params: {
                    ...params,
                    ...extraParams
                }
            })
                .then(response => {
                    //console.log('responce.data: ', response.data);

                    let data = response.data;

                    if (this.init) {
                        this.vocab = data.vocab;
                        this.routes = data.routes;
                        this.permissions = data.permissions;
                        this.channels = data.channels;
                        this.init = '';
                    }
                    this.meta = data.datagrid.meta;

                    //------------ applied + available ------------------------------

                    //console.log('data.datagrid: ', data.datagrid);

                    this.applied.pagination = {
                        current_page: this.meta.paginator.current_page,
                        per_page: this.meta.paginator.per_page,
                    }
                    this.applied.paginator = this.meta.paginator;
                    this.applied.sort = {
                        column: this.meta.sort.column,
                        order: this.meta.sort.order,
                    }
                    this.applied.current_channel_id = this.meta.current_channel_id;
                    this.applied.current_locale_id = this.meta.current_locale_id;

                    this.available.id = data.datagrid.id;
                    this.available.columns = data.datagrid.columns;
                    this.available.actions = data.datagrid.actions;
                    this.available.massActions = data.datagrid.mass_actions;

                    //console.log('axios.get() this.applied: ', this.applied);

                    //---- END --- applied + available ------------------------------

                    //------------ records ------------------------------------------

                    this.records = data.datagrid.records;

                    for (let record of this.records) {
                        if (record['src_id'] == 9) {
                            console.log('record.active: ', record['portfolio_variations.active']);
                        }
                    }

                    //---- END --- records ------------------------------------------

                    //this.ch_lo = data.ch_lo;
                    this.channels = data.channels;


                    this.updateDatagrids();

                    document.title = this.vocab.title;

                    this.update();

                    this.loading = false;
                })
                .catch((error) => {
                    console.log(error);
                });
        },

        getAppliedColumnValues(columnIndex) {
            let appliedColumn = this.findAppliedColumn(columnIndex);
            return appliedColumn?.value ?? [];
        },

        applyChanelAndLocale(channel_id, locale_id) {
            //console.log('applyChanelAndLocale(channel_id: ' + channel_id + ', locale_id: ' + locale_id + ')');
            this.applied.current_channel_id = channel_id;
            this.applied.current_locale_id = locale_id;
            this.get();
        },

        findAppliedColumn(columnIndex) {
            return this.applied.filters.columns.find(column => column.index === columnIndex);
        },

        /**
         * Sort Page.
         *
         * @param {object} column
         * @returns {void}
         */
        sortPage(column) {
            if (column.sortable) {
                this.applied.sort = {
                    column: column.index,
                    order: this.applied.sort.order === 'asc' ? 'desc' : 'asc',
                };

                /**
                 * When the sorting changes, we need to reset the page.
                 */
                this.applied.pagination.current_page = 1;

                this.get();
            }
        },

        applyFilter(column, requestedValue, additional = {}) {
            let appliedColumn = this.findAppliedColumn(column?.index);

            //console.log('appliedColumn: ', appliedColumn);

            /**
             * If no column is found, it means that search from the toolbar have been
             * activated. In this case, we will search for `all` indices and update the
             * value accordingly.
             */
            if (!column) {
                let appliedColumn = this.findAppliedColumn('all');

                if (!requestedValue) {
                    appliedColumn.value = [];

                    return;
                }

                if (appliedColumn) {
                    appliedColumn.value = [requestedValue];
                } else {
                    this.applied.filters.columns.push({
                        index: 'all',
                        value: [requestedValue]
                    });
                }

                /**
                 * Else, we will look into the sidebar filters and update the value accordingly.
                 */
            } else {
                /**
                 * Here if value already exists, we will not do anything.
                 */
                if (
                    requestedValue === undefined ||
                    requestedValue === '' ||
                    appliedColumn?.value.includes(requestedValue)
                ) {
                    return;
                }

                switch (column.type) {
                    case 'date_range':
                    case 'datetime_range':
                        let {
                            range
                        } = additional;

                        if (appliedColumn) {
                            let appliedRanges = appliedColumn.value[0];

                            if (range.name == 'from') {
                                appliedRanges[0] = requestedValue;
                            }

                            if (range.name == 'to') {
                                appliedRanges[1] = requestedValue;
                            }

                            appliedColumn.value = [appliedRanges];
                        } else {
                            let appliedRanges = ['', ''];

                            if (range.name == 'from') {
                                appliedRanges[0] = requestedValue;
                            }

                            if (range.name == 'to') {
                                appliedRanges[1] = requestedValue;
                            }

                            this.applied.filters.columns.push({
                                ...column,
                                value: [appliedRanges]
                            });
                        }

                        break;

                    default:
                        if (appliedColumn) {
                            appliedColumn.value.push(requestedValue);
                        } else {
                            this.applied.filters.columns.push({
                                ...column,
                                value: [requestedValue]
                            });
                        }

                        break;
                }
            }
        },

        /**
         * Filter Page.
         *
         * @param {object} $event
         * @param {object} column
         * @param {object} additional
         * @returns {void}
         */
        filterPage($event, column = null, additional = {}) {
            //console.log('$event: ', $event);
            let quickFilter = additional?.quickFilter;

            if (quickFilter?.isActive) {
                let options = quickFilter.selectedFilter;

                switch (column.type) {
                    case 'date_range':
                    case 'datetime_range':
                        this.applyFilter(column, options.from, {
                            range: {
                                name: 'from'
                            }
                        });

                        this.applyFilter(column, options.to, {
                            range: {
                                name: 'to'
                            }
                        });

                        break;

                    default:
                        break;
                }
            } else {
                console.log('$event?.target?.value: ', $event?.target?.value);
                /**
                 * Here, either a real event will come or a string value. If a string value is present, then
                 * we create a similar event-like structure to avoid any breakage and make it easy to use.
                 */
                if ($event?.target?.value === undefined) {
                    $event = {
                        target: {
                            value: $event,
                        }
                    };
                }

                this.applyFilter(column, $event.target.value, additional);

                if (column) {
                    $event.target.value = '';
                }
            }

            /**
             * We need to reset the page on filtering.
             */
            this.applied.pagination.current_page = 1;

            this.get();
        },

        /**
         * Change Page.
         *
         * The reason for choosing the numeric approach over the URL approach is to prevent any conflicts with our existing
         * URLs. If we were to use the URL approach, it would introduce additional arguments in the `get` method, necessitating
         * the addition of a `url` prop. Instead, by using the numeric approach, we can let Axios handle all the query parameters
         * using the `applied` prop. This allows for a cleaner and more straightforward implementation.
         *
         * @param {string|integer} directionOrPageNumber
         * @returns {void}
         */
        changePage(directionOrPageNumber) {

            console.log('changePage(' + directionOrPageNumber + ')');

            let newPage;

            if (typeof directionOrPageNumber === 'string') {
                if (directionOrPageNumber === 'previous') {
                    newPage = this.applied.pagination.current_page - 1;
                } else if (directionOrPageNumber === 'next') {
                    newPage = this.applied.pagination.current_page + 1;
                    console.log('newPage: ', newPage);
                } else {
                    //console.warn('Invalid Direction Provided : ' + directionOrPageNumber);
                    return;
                }
            } else if (typeof directionOrPageNumber === 'number') {
                newPage = directionOrPageNumber;
            } else {
                //console.warn('Invalid Input Provided: ' + directionOrPageNumber);
                return;
            }

            /**
             * Check if the `newPage` is within the valid range.
             */
            if (newPage >= 1 && newPage <= this.meta.paginator.last_page) {
                this.applied.pagination.current_page = newPage;
                this.get();
            } else {
                //console.warn('Invalid Page Provided: ' + newPage);
            }
        },

        /**
         * Change per page option.
         *
         * @param {integer} option
         * @returns {void}
         */
        changePerPageOption(option) {
            this.applied.pagination.per_page = option;
            this.applied.pagination.current_page = 1;

            // /**
            //  * When the total records are less than the number of data per page, we need to reset the page.
            //  */
            // if (this.applied.pagination.last_page >= this.applied.pagination.page) {
            //     this.applied.pagination.page = 1;
            // }

            this.get();
        },

        onRowsSortDragEnd(ids) {
            // --------- OLD CODE ---
            // const rows = document.getElementsByClassName('sortable_row');
            // let ids = [];
            // rows.forEach((row) => {
            //     var _id_n = (this.meta.has_variations && !this.applied.sort.column
            //         .includes('variation')) ? 'src_id' : 'row_id';
            //     ids.push(row.getAttribute(_id_n));
            // })
            // ---END--- OLD CODE ---

            //console.log('ids: ', ids);
            if (this.applied.sort.order == 'desc') {
                ids.reverse()
            }

            axios['patch']('/admin/' + this.module + '/save-positions', {
                ids: ids,
                column: this.applied.sort.column,
                channel_id: this.applied.current_channel_id,
                locale_id: this.applied.current_locale_id,
            })
                .then((response) => {
                    //console.log('response.data: ', response.data);
                    var flash_type = (response.data.type == 'error') ? 'error' : 'success';
                    this.$emitter.emit('add-flash', {
                        type: flash_type,
                        message: response.data.message
                    });
                })
                .catch((error) => {
                    this.$emitter.emit('add-flash', {
                        type: 'error',
                        message: response.data.message
                    });
                });
        },

        getDatagridsStorageKey() {
            return 'dgrids';
        },

        getDatagrids() {
            let datagrids = localStorage.getItem(
                this.getDatagridsStorageKey()
            );
            return JSON.parse(datagrids) ?? [];
        },

        updateDatagrids() {

            let datagrids = this.getDatagrids();
            if (datagrids?.length) {

                const currentDatagrid = datagrids.find(({
                    module
                }) => module === this.module);

                if (currentDatagrid) {
                    datagrids = datagrids.map(datagrid => {
                        if (datagrid.module === this.module) {
                            return {
                                ...datagrid,
                                requestCount: ++datagrid.requestCount,
                                //available: this.available,
                                applied: this.applied,
                            };
                        }

                        return datagrid;
                    });
                } else {
                    datagrids.push(this.getDatagridInitialProperties());
                }
            } else {
                datagrids = [this.getDatagridInitialProperties()];
            }

            this.setDatagrids(datagrids);
        },

        getDatagridInitialProperties() {

            var applied = {
                current_channel_id: this.applied.current_channel_id,
                current_locale_id: this.applied.current_locale_id,
                pagination: this.applied.pagination,
                sort: this.applied.sort,
                filters: this.applied.filters,
            };

            var a = {
                module: this.module,
                requestCount: 0,
                available: this.available,
                applied: this.applied,
            };

            console.log('a: ', a);

            return a;
        },

        setDatagrids(datagrids) {
            localStorage.setItem(
                this.getDatagridsStorageKey(),
                JSON.stringify(datagrids)
            );
        },

        rowAction(method, data) {
            axios['patch']('/admin/' + this.module + '/row-action', {
                method: method,
                data: {
                    id: (!data.column.index.includes('_variation') && data.record.src_id) ? data.record.src_id : data.record.id,
                    index: data.column.index
                }
            })
                .then((response) => {
                    var flash_type = (response.data.type == 'error') ? 'error' : 'success';
                    this.$emitter.emit('add-flash', {
                        type: flash_type,
                        message: response.data.message
                    });
                })
                .catch((error) => {
                    this.$emitter.emit('add-flash', {
                        type: 'error',
                        message: response.data.message
                    });
                });
        },


        performAction(action) {
            const method = action.method.toLowerCase();
            switch (method) {
                case 'get':
                    //console.log('action: ', action);
                    //this.$emitter.emit(action.action, {route: action.url, use_route: false, id: action.id});
                    window.location.href = action.url;
                    break;
                case 'post':
                case 'put':
                case 'patch':
                case 'delete':
                    //console.log('action: ', action);

                    var options = {
                        title: action.title,
                        message: action.message
                    };
                    if (action.confirmation?.title) {
                        options.title = action.confirmation.title
                    }
                    if (action.confirmation?.message) {
                        options.message = action.confirmation.message
                    }

                    if (action.confirmation?.buttons) {
                        action.confirmation.buttons.forEach((button) => {
                            button.callback = () => {
                                //console.log(data);
                                //console.log(data.method);
                                console.info(button);
                                axios[button.method](button.url)
                                    .then(response => {
                                        this.$emitter.emit('add-flash', {
                                            type: response.data.type ?? 'success',
                                            message: response.data.message
                                        });

                                        this.get();
                                    })
                                    .catch((error) => {
                                        this.$emitter.emit('add-flash', {
                                            type: 'error',
                                            message: error.response.data.message
                                        });
                                    });
                            }
                        })

                        options.buttons = action.confirmation.buttons;
                    } else {

                        options.agree = () => {
                            axios[method](action.url)
                                .then(response => {
                                    this.$emitter.emit('add-flash', {
                                        type: 'success',
                                        message: response.data.message
                                    });

                                    this.get();
                                })
                                .catch((error) => {
                                    this.$emitter.emit('add-flash', {
                                        type: 'error',
                                        message: error.response.data.message
                                    });
                                });
                        }

                    }

                    console.log('options: ', options);

                    this.$emitter.emit('open-confirm-modal', options);
                    break;
                default:
                    console.error('Method not supported.');
                    break;
            }
        },

        //================================================================
        // Mass actions logic, will move it from here once completed.
        //================================================================

        setCurrentSelectionMode() {
            //console.log('this.meta.primary_column: ', this.meta.primary_column);
            this.applied.massActions.meta.mode = 'none';

            if (!this.records.length) {
                return;
            }

            let selectionCount = 0;

            this.records.forEach(record => {
                const id = record[this.meta.primary_column];

                if (this.applied.massActions.indices.includes(id)) {
                    this.applied.massActions.meta.mode = 'partial';

                    ++selectionCount;
                }
            });

            if (this.records.length === selectionCount) {
                this.applied.massActions.meta.mode = 'all';
            }
        },

        selectAllRecords() {
            this.setCurrentSelectionMode();

            if (['all', 'partial'].includes(this.applied.massActions.meta.mode)) {
                this.records.forEach(record => {
                    const id = record[this.meta.primary_column];

                    this.applied.massActions.indices = this.applied.massActions.indices.filter(selectedId => selectedId !== id);
                });

                this.applied.massActions.meta.mode = 'none';
            } else {
                this.records.forEach(record => {
                    const id = record[this.meta.primary_column];

                    let found = this.applied.massActions.indices.find(selectedId => selectedId === id);

                    if (!found) {
                        this.applied.massActions.indices.push(id);
                    }
                });

                this.applied.massActions.meta.mode = 'all';
            }
        },

        validateMassAction() {
            if (!this.applied.massActions.indices.length) {
                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.components.datagrid.index.no-records-selected')" });

                return false;
            }

            if (!this.applied.massActions.meta.action) {
                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.components.datagrid.index.must-select-a-mass-action')" });

                return false;
            }

            if (
                this.applied.massActions.meta.action?.options?.length &&
                this.applied.massActions.value === null
            ) {
                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('admin::app.components.datagrid.index.must-select-a-mass-action-option')" });

                return false;
            }

            return true;
        },


        // massActionProcess(action) {
        //     console.log('++action: ', action);
        //     let method = action.method.toLowerCase();

        //     console.log('method: ', method);
        //     switch (method) {
        //         case 'post':
        //         case 'put':
        //         case 'patch':
        //             //console.info();
        //             // if (action.confirmation?.buttons) {
        //             //     console.log('action.confirmation?.buttons: ', action.confirmation.buttons);
        //             //     action.confirmation.buttons.forEach((button) => {
        //             //         console.log('button');
        //             //         button.callback = () => {
        //             //             //console.log(data);
        //             //             //console.log(data.method);
        //             //             axios[button.method](button.url)
        //             //                 .then(response => {
        //             //                     this.$emitter.emit('add-flash', {
        //             //                         type: response.data.type ?? 'success',
        //             //                         message: response.data.message
        //             //                     });

        //             //                     this.get();
        //             //                 })
        //             //                 .catch((error) => {
        //             //                     this.$emitter.emit('add-flash', {
        //             //                         type: 'error',
        //             //                         message: error.response.data.message
        //             //                     });
        //             //                 });
        //             //         }
        //             //     })

        //             //     options.buttons = action.confirmation.buttons;
        //             // } else {

        //             // }





        //             // axios[method](action.url, {
        //             //     indices: this.applied.massActions.indices,
        //             //     value: this.applied.massActions.value,
        //             //     action: action.action,
        //             // })
        //             //     .then(response => {
        //             //         this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
        //             //         this.get();
        //             //     })
        //             //     .catch((error) => {
        //             //         this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
        //             //     });

        //             break;

        //         case 'delete':
        //             axios[method](action.url, {
        //                 indices: this.applied.massActions.indices
        //             })
        //                 .then(response => {
        //                     this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

        //                     this.get();
        //                 })
        //                 .catch((error) => {
        //                     this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
        //                 });

        //             break;

        //         default:
        //             console.error('Method not supported.');

        //             break;
        //     }

        //     this.applied.massActions = {
        //         meta: {
        //             mode: 'none',
        //             action: null,
        //         },
        //         indices: [],
        //         value: null,
        //     };
        // },

        performMassAction(currentAction, currentOption = null) {
            //console.log('performMassAction currentAction', currentAction);
            //console.log('performMassAction currentOption', currentOption);

            this.applied.massActions.meta.action = currentAction;

            if (currentOption) {
                this.applied.massActions.value = currentOption.value;
            }

            if (!this.validateMassAction()) {
                alert('! this.validateMassAction()');
                return;
            }

            var options = {
                title: currentAction.confirmation.title,
                message: currentAction.confirmation.message,
                //buttons: []
            };

            currentAction.confirmation.buttons.forEach((button) => {

                var params = {
                    indices: this.applied.massActions.indices,
                    value: this.applied.massActions.value,
                    action: button.action,
                    channel_id: this.applied.current_channel_id,
                    locale_id: this.applied.current_locale_id
                }

                button.callback = () => {
                    let method_name = button.method.toLowerCase();
                    axios[method_name](button.url, params)
                        .then(response => {
                            this.$emitter.emit('add-flash', {
                                type: response.data.type ?? 'success',
                                message: response.data.message
                            });

                            this.applied.massActions = {
                                meta: {
                                    mode: 'none',
                                    action: null,
                                },
                                indices: [],
                                value: null,
                            };

                            this.get();

                        })
                        .catch((error) => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response.data.message
                            });
                        });
                }
            })

            options.buttons = currentAction.confirmation.buttons;




            // var button;
            // for (var i = 0; i < currentAction.confirmation.buttons.length; i++) {
            //     button = currentAction.confirmation.buttons[i];
            //     //console.info('button: ', button);
            //     //console.info('currentAction.confirmation.buttons[i]: ', currentAction.confirmation.buttons[i]);
            //     button.callback = () => this.massActionProcess(button);
            //     // currentAction.confirmation.buttons[i].callback = () => {
            //     //     console.log('------currentAction.confirmation.buttons[i]: ', currentAction.confirmation.buttons[i]);
            //     //console.log('------button: ', button);
            //     //     this.massActionProcess(currentAction.confirmation.buttons[i]);
            //     // }
            //     options.buttons.push(button);
            // }

            // console.log('performMassAction options.buttons:', options.buttons);

            // console.log('performMassAction options:', options);

            this.$emitter.emit('open-confirm-modal', options);

        },
    },

};
</script>

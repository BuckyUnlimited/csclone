<template>
  <div class="card">
    <div class="card-header">
      <div class="d-flex justify-content-between">
        <h3 class="card-title my-auto">{{ props.title }}</h3>
        <div class="d-flex justify-content-end">
          <div class="card-tools">
            <div class="input-group input-group">
              <input v-model="filter" type="text" class="form-control float-right" :placeholder="'Search'" />
              <div class="input-group-append">
                <button class="btn btn-default">
                  <i class="fas fa-search"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body table-responsive p-0">
      <table class="text-nowrap table-head-fixed table-valign-middle table table-head-fixed table-bordered table-hover">
        <thead class="text-center">
          <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <th v-for="header in headerGroup.headers" :key="header.id"
              :class="{ 'can-sort': header.column.getCanSort() }"
              @click="header.column.getToggleSortingHandler()?.($event)">
              <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
              {{ { asc: " ↓", desc: " ↑" }[header.column.getIsSorted()] }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in table.getRowModel().rows" :key="row.id">
            <td v-for="cell in row.getVisibleCells()" :key="cell.id">
              <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="card-footer clearfix" v-if="!props.maxPageSize">
      <div class="row">
        <div class="col-md text-nowrap mb-2">
          <div class="d-flex justify-content-between">
            <div class="col-auto my-auto">
              <span>Page {{ table.getState().pagination.pageIndex + 1 }} of
                {{ table.getPageCount() }} -
                {{ table.getFilteredRowModel().rows.length }}
                {{
                  table.getFilteredRowModel().rows.length !== 1 ? "results" : "result"
                }}</span>
            </div>
            <div class="col-auto">
              <div class="input-group input-group">
                <div class="input-group-prepend">
                  <button class="btn btn-default">Show</button>
                </div>
                <select v-model="pageSize" class="form-control">
                  <option v-for="size in [10, 25, 50, 100, 250]" :key="size" :value="size">
                    {{ size }}
                  </option>

                  <option :value="table.getFilteredRowModel().rows.length">Max</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-auto">
          <div class="d-flex justify-content-center">
            <div class="dataTables_paginate paging_simple_numbers">
              <ul class="pagination">
                <li class="paginate_button page-item" :class="{ disabled: !table.getCanPreviousPage() }">
                  <a @click="table.setPageIndex(0)" role="button" tabindex="0" class="page-link"><i
                      class="fas fa-angle-double-left"></i></a>
                </li>
                <li class="paginate_button page-item" :class="{ disabled: !table.getCanPreviousPage() }">
                  <a @click="table.previousPage()" role="button" tabindex="0" class="page-link"><i
                      class="fas fa-angle-left"></i></a>
                </li>

                <li v-if="currentPage > sidePage" class="paginate_button page-item">
                  <a role="button" tabindex="0" class="page-link">...</a>
                </li>
                <template v-if="table.getPageCount() > 0" v-for="index in table.getPageCount()" :key="index">
                  <li v-if="
                    index > currentPage - sidePage && index < currentPage + 2 + sidePage
                  " class="paginate_button page-item" :class="{ active: index - 1 === currentPage }">
                    <a @click="table.setPageIndex(index - 1)" role="button" tabindex="0" class="page-link">{{ index
                    }}</a>
                  </li>
                </template>
                <li v-if="currentPage + 1 < table.getPageCount() - sidePage" class="paginate_button page-item">
                  <a role="button" tabindex="0" class="page-link">...</a>
                </li>

                <li class="paginate_button page-item" :class="{ disabled: !table.getCanNextPage() }">
                  <a @click="table.nextPage()" role="button" tabindex="0" class="page-link"><i
                      class="fas fa-angle-right"></i></a>
                </li>
                <li class="paginate_button page-item" :class="{ disabled: !table.getCanNextPage() }">
                  <a @click="table.setPageIndex(table.getPageCount() - 1)" role="button" tabindex="0"
                    class="page-link"><i class="fas fa-angle-double-right"></i></a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
.can-sort {
  cursor: pointer;
  user-select: none;
}
</style>
<script setup>
import { computed, onBeforeUpdate, ref, watch } from "vue";
import {
  useVueTable,
  FlexRender,
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  getFilteredRowModel,
} from "@tanstack/vue-table";
const props = defineProps({
  title: String,
  data: Array,
  columns: Array,
  maxPageSize: {
    type: Boolean,
    default: false,
  },
  pageSize: {
    type: Number,
    default: 25,
    validator: (value) => [10, 25, 50, 100, 250].includes(value),
  },
});
const sidePage = ref(3);

const sorting = ref([]);
const filter = ref("");
const currentPage = ref(0);
const pageSize = ref(
  props.maxPageSize && props.data.length ? props.data.length : props.pageSize
);
const columns = ref(props.columns);
const table = computed(() =>
  useVueTable({
    data: props.data,
    columns: columns.value,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    state: {
      get sorting() {
        return sorting.value;
      },
      get globalFilter() {
        return replaceUnicode(filter.value);
      },
    },
    initialState: {
      pagination: {
        pageIndex: currentPage.value,
        pageSize: pageSize.value,
      },
    },
    onSortingChange: (updaterOrValue) => {
      sorting.value =
        typeof updaterOrValue === "function"
          ? updaterOrValue(sorting.value)
          : updaterOrValue;
    },
  })
);

const showedPage = ref(null);
onBeforeUpdate(() => {
  if (filter.value !== "") {
    if (!showedPage.value) {
      showedPage.value = table.value.getState().pagination.pageIndex;
    }
    // currentPage.value = 0;
    if (table.value.getPageCount() <= currentPage.value) {
      currentPage.value = 0;
    } else {
      currentPage.value = table.value.getState().pagination.pageIndex;
    }
  } else {
    if (showedPage.value && showedPage.value !== currentPage.value) {
      currentPage.value = showedPage.value;
      showedPage.value = null;
    } else {
      currentPage.value = table.value.getState().pagination.pageIndex;
    }
  }
  columns.value = [...props.columns];
});

watch([() => props.data, pageSize], (nv, ov) => {
  currentPage.value = 0;
});

function replaceUnicode(text) {
  const salabpi = ["ង", "ញ", "ប", "ម", "យ", "រ", "វ"];
  const treysab = ["ស", "ហ", "អ"];
  const chars = salabpi.concat(treysab);
  const vowels = ["ិ", "ី", "ឹ", "ឺ", "ើ"];
  text = text
    .replaceAll("្" + "ដ", "្ត")
    .replaceAll("ា" + "ំ", "ាំ")
    .replaceAll("េ" + "ី", "ើ")
    .replaceAll("េ" + "ា", "ោ")
    .replaceAll("េ" + "ះ", "េះ")
    .replaceAll("ោ" + "ះ", "ោះ")
    .replaceAll("េ" + "ុ" + "ី", "ុ" + "ើ");
  for (const char of chars) {
    for (const vowel of vowels) {
      let replacementSign = "";
      if (salabpi.includes(char)) {
        replacementSign = "៉";
      } else if (treysab.includes(char)) {
        replacementSign = "៊";
      } else {
        continue;
      }
      const word = char + "ុ" + vowel;
      const replacement = char + replacementSign + vowel;
      text = text.replaceAll(word, replacement);
    }
  }
  return text;
}
</script>

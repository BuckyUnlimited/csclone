<template>
  <div class="card">
    <div class="card-header">
      <div class="d-flex justify-content-between">
        <h3 class="card-title my-auto">{{ title }}</h3>
        <div class="d-flex justify-content-end">
          <div class="card-tools">
            <div class="input-group input-group">
              <input v-model="keyword" type="text" class="form-control float-right" placeholder="Search"
                @keyup.enter="handleSearch" />
              <div class="input-group-append">
                <button class="btn btn-default" @click="handleSearch" type="button">
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
            <th v-for="header in headerGroup.headers" :key="header.id">
              <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
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
    <div class="card-footer clearfix">
      <div class="row">
        <div class="col-md text-nowrap mb-2">
          <div class="d-flex justify-content-between">
            <div class="col-auto my-auto">
              <span>Page {{ currentPage }} of {{ lastPage }} - {{ total }} {{ total !== 1 ? "results" : "result"
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
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-auto">
          <div class="d-flex justify-content-center">
            <div class="dataTables_paginate paging_simple_numbers">
              <ul class="pagination">
                <!-- First page -->
                <li class="paginate_button page-item" :class="{ disabled: currentPage === 1 }">
                  <a @click.prevent="currentPage > 1 && changePage(1)" role="button" tabindex="0" class="page-link"
                    :style="{ cursor: currentPage === 1 ? 'not-allowed' : 'pointer' }">
                    <i class="fas fa-angle-double-left"></i>
                  </a>
                </li>
                <!-- Previous page -->
                <li class="paginate_button page-item" :class="{ disabled: currentPage === 1 }">
                  <a @click.prevent="currentPage > 1 && changePage(currentPage - 1)" role="button" tabindex="0"
                    class="page-link" :style="{ cursor: currentPage === 1 ? 'not-allowed' : 'pointer' }">
                    <i class="fas fa-angle-left"></i>
                  </a>
                </li>

                <!-- Ellipsis before -->
                <li v-if="currentPage > sidePage" class="paginate_button page-item">
                  <a class="page-link" style="cursor: default;">...</a>
                </li>

                <!-- Page numbers -->
                <template v-for="pageNum in lastPage" :key="pageNum">
                  <li v-if="pageNum >= currentPage - sidePage && pageNum <= currentPage + sidePage"
                    class="paginate_button page-item" :class="{ active: pageNum === currentPage }">
                    <a @click.prevent="changePage(pageNum)" role="button" tabindex="0" class="page-link"
                      style="cursor: pointer;">{{ pageNum }}</a>
                  </li>
                </template>

                <!-- Ellipsis after -->
                <li v-if="currentPage < lastPage - sidePage" class="paginate_button page-item">
                  <a class="page-link" style="cursor: default;">...</a>
                </li>

                <!-- Next page -->
                <li class="paginate_button page-item" :class="{ disabled: currentPage === lastPage }">
                  <a @click.prevent="currentPage < lastPage && changePage(currentPage + 1)" role="button" tabindex="0"
                    class="page-link" :style="{ cursor: currentPage === lastPage ? 'not-allowed' : 'pointer' }">
                    <i class="fas fa-angle-right"></i>
                  </a>
                </li>
                <!-- Last page -->
                <li class="paginate_button page-item" :class="{ disabled: currentPage === lastPage }">
                  <a @click.prevent="currentPage < lastPage && changePage(lastPage)" role="button" tabindex="0"
                    class="page-link" :style="{ cursor: currentPage === lastPage ? 'not-allowed' : 'pointer' }">
                    <i class="fas fa-angle-double-right"></i>
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed, ref, toRefs } from "vue";
import {
  useVueTable,
  FlexRender,
  getCoreRowModel,
} from "@tanstack/vue-table";

const emit = defineEmits(['searchChange']);

const props = defineProps({
  title: String,
  data: Array,
  columns: Array,
});

// Two-way binding models
const pageSize = defineModel('pageSize', { type: Number, default: 25, validator: (value) => [10, 25, 50, 100, 250].includes(value) });
const currentPage = defineModel('currentPage', { type: Number, default: 1 });
const lastPage = defineModel('lastPage', { type: Number, default: 1 });
const total = defineModel('total', { type: Number, default: 0 });
const keyword = defineModel('keyword', { type: String, default: "" });

// Destructure props for cleaner template access
const { title, data, columns } = toRefs(props);

// Local state
const sidePage = ref(3);

// Table for rendering (no client-side pagination)
const table = computed(() =>
  useVueTable({
    data: data.value,
    columns: columns.value,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
  })
);

// Event handlers
function handleSearch() {
  emit('searchChange', keyword.value);
}

function changePage(page) {
  if (page >= 1 && page <= lastPage.value && page !== currentPage.value) {
    currentPage.value = page;
  }
}
</script>

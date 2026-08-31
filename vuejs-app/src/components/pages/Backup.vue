<template>
  <div class="content-wrapper" style="min-height: 1416px">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Backups</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link :to="{ name: 'dashboard' }">Home</router-link>
              </li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <CustomTable :title="'Backups'" :data="backups" :columns="columns" />
      </div>
    </section>
  </div>
</template>

<script setup>
import Swal from "sweetalert2";
import { apiGetBackups, apiCreateBackup, apiDownloadBackup, apiDeleteBackup } from "@/functions/api/backup";
import { CloseModal, LoadingModal, MessageModal } from "@/functions/swal";
import { onMounted, ref, h } from "vue";
import CustomTable from "@/components/includes/controls/CustomTable.vue";

const total_size_human = ref(0);
const backups = ref([]);
const columns = [
  {
    header: "Filename",
    accessorKey: "filename",
  },
  {
    header: () => "Size (Total: " + total_size_human.value + ")",
    accessorKey: "size_human",
  },
  {
    header: "Created At",
    accessorKey: "date_human",
  },
  {
    accessorKey: "action",
    header: () => [
      "Actions",
      h(
        "button",
        {
          onClick: () => createBackup(),
          class: "btn btn-sm btn-success ml-3",
        },
        "Create"
      ),
    ],
    cell: ({
      row: {
        original: { filename },
      },
    }) => [
        // download btn
        h(
          "button",
          {
            onClick: () => downloadBackup(filename),
            class: "btn btn-sm btn-outline-primary mx-1",
            title: "Download Backup",
          },
          h("i", { class: "fa fa-download" })
        ),
        // delete btn
        h(
          "button",
          {
            onClick: () => removeBackup(filename),
            class: "btn btn-sm btn-outline-danger mx-1",
            title: "Delete Backup",
          },
          h("i", { class: "fa fa-trash" })
        ),
      ],
    enableSorting: false,
  },
];

onMounted(async () => {
  try {
    LoadingModal();
    await generateBackups();
    return CloseModal();
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
});

async function generateBackups() {
  const response = await apiGetBackups();
  backups.value = response.data.backups;
  total_size_human.value = response.data.total_size_human;
}

async function createBackup() {
  Swal.fire({
    icon: "info",
    title: "Create Backup",
    text: "Are you sure you want to create a new backup? This may take some time.",
    showCancelButton: true,
    confirmButtonColor: "#28a745",
    confirmButtonText: "Yes, create it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        LoadingModal();
        const response = await apiCreateBackup();
        return MessageModal({ icon: "success", title: "Success", text: response.data.message });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
      }
    }
  });
}

async function downloadBackup(filename) {
  try {
    LoadingModal();
    const response = await apiDownloadBackup(filename);
    CloseModal();

    // Create blob and download
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    link.parentNode.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
  }
}

async function removeBackup(filename) {
  Swal.fire({
    icon: "warning",
    title: "Delete Backup",
    text: "Are you sure you want to delete this backup? This action cannot be undone.",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        LoadingModal();
        const response = await apiDeleteBackup(filename);
        onBackupDelete(filename);
        return MessageModal({ icon: "success", title: "Success", text: response.data.message });
      } catch (error) {
        return MessageModal({ icon: "error", title: "Error", text: error.response?.data?.message || error.message });
      }
    }
  });
}

function onBackupDelete(filename) {
  backups.value = backups.value.filter((b) => b.filename !== filename);
  total_size_human.value = formatBytes(backups.value.reduce((acc, b) => acc + b.size, 0));
}

function formatBytes(bytes, precision = 2) {
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  bytes = Math.max(bytes, 0);
  const pow = Math.floor((bytes ? Math.log(bytes) : 0) / Math.log(1024));
  const powIndex = Math.min(pow, units.length - 1);
  bytes /= Math.pow(1024, powIndex);
  return bytes.toFixed(precision) + ' ' + units[powIndex];
}
</script>

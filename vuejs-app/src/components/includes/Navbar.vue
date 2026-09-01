<template>
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <RouterLink class="nav-link" :to="{ name: 'chat.create' }" role="button">
          <i class="fas fa-comment-medical text-primary"></i>
        </RouterLink>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
          <i class="fas fa-th-large"></i>
        </a>
      </li>
      <li class="nav-item">
        <a @click="signOut" class="nav-link" role="button">
          <i class="fas fa-sign-out-alt text-danger"></i>
        </a>
      </li>
    </ul>
  </nav>
</template>

<script setup>
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
const router = useRouter();

async function signOut() {
  await Swal.fire({
    title: 'Are you sure?',
    text: "You will be signed out from the system!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, sign me out!'
  }).then((result) => {
    if (result.isConfirmed) {
      return router.push({ name: 'auth.signout' });
    }
  });
}
</script>

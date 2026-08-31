import axios from 'axios';

const APP_API_URL = import.meta.env.VITE_APP_API_URL;

export function apiGetBackups() {
  return axios.get(APP_API_URL + '/manage/backups');
}

export function apiCreateBackup() {
  return axios.post(APP_API_URL + '/manage/backups/create');
}

export function apiDownloadBackup(filename) {
  return axios.get(APP_API_URL + `/manage/backups/download/${filename}`, {
    responseType: 'blob'
  });
}

export function apiDeleteBackup(filename) {
  return axios.delete(APP_API_URL + `/manage/backups/delete/${filename}`);
}

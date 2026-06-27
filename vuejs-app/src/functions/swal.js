import Swal from 'sweetalert2';

export const LoadingModal = (text = 'Loading...') => {
  return Swal.fire({
    text: text,
    allowOutsideClick: false,
    allowEscapeKey: false,
    preConfirm: () => false,
    width: '200px',
  }).then(Swal.showLoading());
}
export const MessageModal = async (options, callback) => {
  return await Swal.fire({
    ...options,
    showConfirmButton: false,
  }).then(async () => {
    if (typeof callback === "function") {
      return await callback();
    }
  })
}
export const CloseModal = () => {
  return Swal.close();
}

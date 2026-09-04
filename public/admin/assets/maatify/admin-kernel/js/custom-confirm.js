window.appConfirm = function ({
                                  title = '',
                                  message = '',
                                  type = 'danger'
                              }) {

    return new Promise((resolve) => {

        const overlay = document.getElementById('confirm-overlay');
        const titleEl = document.getElementById('confirm-title');
        const messageEl = document.getElementById('confirm-message');
        const okBtn = document.getElementById('confirm-ok');
        const cancelBtn = document.getElementById('confirm-cancel');
        const closeBtn = document.getElementById('confirm-close');

        if (!overlay || !titleEl || !messageEl || !okBtn || !cancelBtn) {
            console.error('Confirm modal elements not found');
            resolve(false);
            return;
        }

        titleEl.textContent = title;
        messageEl.textContent = message;

        overlay.classList.remove('hidden');

        function cleanup(result) {

            overlay.classList.add('hidden');

            okBtn.removeEventListener('click', okHandler);
            cancelBtn.removeEventListener('click', cancelHandler);
            closeBtn.removeEventListener('click', cancelHandler);

            resolve(result);
        }

        function okHandler() {
            cleanup(true);
        }

        function cancelHandler() {
            cleanup(false);
        }

        okBtn.addEventListener('click', okHandler);
        cancelBtn.addEventListener('click', cancelHandler);
        closeBtn.addEventListener('click', cancelHandler);

    });

};

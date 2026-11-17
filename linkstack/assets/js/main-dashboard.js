
(function ($) {

    "use strict";

    // Prévia é carregada apenas enquanto o modal fullscreen está aberto.
    // Evita a referência aos iframes removidos frPreview1/frPreview2.
    const refreshStudioPreview = function () {
        const frame = document.getElementById('studio-links-preview-frame');
        if (frame && frame.hasAttribute('src')) {
            frame.src = frame.dataset.previewUrl || frame.src;
        }
    };

    document.addEventListener('click', async function (event) {
        const control = event.target.closest('[data-delete-link]');
        if (!control) return;
        event.preventDefault();
        if (control.dataset.pending === 'true') return;
        if (!window.confirm(control.dataset.confirm || 'Excluir este link?')) return;
        control.dataset.pending = 'true';
        control.setAttribute('aria-disabled', 'true');
        const row = control.closest('[data-id]');
        let feedback = document.getElementById('link-delete-feedback');
        if (!feedback) {
            feedback = document.createElement('p');
            feedback.id = 'link-delete-feedback';
            feedback.setAttribute('role', 'status');
            document.getElementById('links-table-body').before(feedback);
        }
        feedback.textContent = 'Excluindo link…';
        const abort = new AbortController();
        const timer = setTimeout(() => abort.abort(), 20000);
        try {
            const response = await fetch(control.href, {
                method: 'DELETE', credentials: 'same-origin', signal: abort.signal,
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            if (!response.ok) throw new Error('delete failed');
            const result = await response.json();
            if (!result.deleted || !row || String(result.id) !== row.dataset.id) throw new Error('invalid response');
            const next = row.nextElementSibling || row.previousElementSibling;
            // Tooltip versions differ between layouts; cleanup must not prevent removal.
            try {
                if (typeof window.bootstrap?.Tooltip?.getInstance === 'function') {
                    window.bootstrap.Tooltip.getInstance(control)?.dispose();
                } else if ($.fn.tooltip) { $(control).tooltip('dispose'); }
            } catch (_) { /* The persisted deletion remains successful. */ }
            row.remove();
            feedback.textContent = document.querySelector('#links-table-body [data-id]')
                ? 'Link excluído e alteração salva.' : 'Link excluído e alteração salva. Não há mais links nesta página.';
            if (next) next.querySelector('a,button')?.focus();
            else { feedback.tabIndex = -1; feedback.focus(); }
            refreshStudioPreview();
        } catch (error) {
            feedback.textContent = 'Não foi possível confirmar a exclusão. Verifique sua conexão e tente novamente.';
            control.removeAttribute('aria-disabled');
            delete control.dataset.pending;
        } finally { clearTimeout(timer); }
    });

    var fullHeight = function () {

        $('.js-fullheight').css('height', $(window).height());
        $(window).resize(function () {
            $('.js-fullheight').css('height', $(window).height());
        });

    };
    fullHeight();

    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    var sortableTbody = document.getElementById("links-table-body");
    if (sortableTbody) {
        const sortableLinkTable = Sortable.create(sortableTbody, {
            handle: ".sortable-handle",
            animation: 150,
            swapThreshold: 0.60,
            ghostClass: 'bg-soft-secondary',
            onChange: function (event) {
            },
            store: {
                get: function (sortable) {
                    var order = linksTableOrders || "";
                    return order ? order.split('|') : [];
                },
                set: function (sortable) {
                    const linkOrders = sortable.toArray();
                    const currentPage = sortableTbody.dataset.page || 1;
                    const perPage = sortableTbody.dataset.perPage || 0;
                    const formData = {
                        'linkOrders': linkOrders,
                        'currentPage': currentPage,
                        'perPage': perPage,
                    };
                    // $.blockUI({
                    // 	message: '<img width="70px" src="img/loading.gif" />',
                    // 	css: {
                    // 		backgroundColor: 'transparent',
                    // 		border: 'none',
                    // 		color: '#444444',
                    // 	}
                    // });

                    // VERY janky solution; have to fix later
                    var str = window.location.pathname;
                    str = str.replace("/studio/links", "");

                    $.post(str + "/studio/sort-link", formData, function (response) {
                        if (response.linkOrders) {
                            $.each(response.linkOrders, function (linkId, linkOrder) {
                                // $("#links-table-body div[data-id='"+linkId+"']")
                                // 	.find("div:eq(3)")
                                // 	.html(linkOrder);
                            });
                            //$.unblockUI();
                        } else {
                            alert("Something went wrong! Please, Try again.")
                        }
                    });

                    // A próxima abertura do modal carrega a página atualizada.
                    // Se já estiver aberto, atualizar o iframe existente sem erro.
                    refreshStudioPreview();
                }
            }
        });
        // Os botões de mover agora são renderizados dentro da mesma linha
        // de Editar/Excluir. Não criar novos controles fora do card.
        const status=document.createElement('p');
        status.setAttribute('role','status');
        status.className='visually-hidden';
        sortableTbody.after(status);
        sortableTbody.addEventListener('click', function(event) {
            const button=event.target.closest('button.studio-link-move');
            if(!button || !sortableTbody.contains(button))return;
            event.preventDefault();
            const row=button.closest('[data-id]');
            if(!row)return;
            const direction=Number(button.dataset.direction);
            if(direction!==-1 && direction!==1)return;
            const sibling=direction===-1 ? row.previousElementSibling : row.nextElementSibling;
            if(!sibling || !sibling.matches('[data-id]'))return;
            if(direction===-1)sortableTbody.insertBefore(row,sibling);
            else sortableTbody.insertBefore(sibling,row);
            sortableLinkTable.save();
            button.focus();
            status.textContent='Ordem atualizada.';
        });
    }



})(jQuery);

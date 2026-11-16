
(function ($) {

    "use strict";

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
            document.querySelectorAll('#frPreview1,#frPreview2').forEach(frame => { frame.src = frame.src; });
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

                    setTimeout(function(){
                        document.getElementById('frPreview1').src += '';
                        document.getElementById('frPreview2').src += '';
                    }, 300);
                }
            }
        });
        const status=document.createElement('p');status.setAttribute('role','status');sortableTbody.after(status);
        Array.from(sortableTbody.children).forEach(row=>{
          if(!row.dataset.id)return;
          const controls=document.createElement('div');controls.className='d-flex gap-1';
          for(const [label,direction] of [['Mover para cima',-1],['Mover para baixo',1]]){
            const button=document.createElement('button');button.type='button';button.className='btn btn-sm btn-outline-secondary';button.textContent=direction===-1?'↑':'↓';button.setAttribute('aria-label',label);
            button.onclick=()=>{const sibling=direction===-1?row.previousElementSibling:row.nextElementSibling;if(!sibling)return;if(direction===-1)sortableTbody.insertBefore(row,sibling);else sortableTbody.insertBefore(sibling,row);sortableLinkTable.save();button.focus();status.textContent='Ordem atualizada.';};controls.append(button);
          }
          row.append(controls);
        });
    }



})(jQuery);

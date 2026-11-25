{{-- Scoped renderer for UserTable. Vendor components still render every row, column, sort and pagination. --}}
<x-livewire-tables::wrapper :component="$this">
    <div class="lf-users-search mb-3" aria-label="Pesquisa de usuários">
        @if($this->searchIsEnabled() && $this->searchVisibilityIsEnabled())
            <label class="form-label fw-semibold" for="lf-users-search-input">Pesquisar usuários</label>
            <div class="input-group">
                <input id="lf-users-search-input" type="search" class="form-control"
                       wire:model{{ $this->getSearchOptions() }}="{{ $this->getTableName() }}.search"
                       placeholder="Nome, e-mail, página ou ID" autocomplete="off">
                @if($this->hasSearch())
                    <button type="button" class="btn btn-outline-secondary" wire:click.prevent="clearSearch" aria-label="Limpar pesquisa">Limpar</button>
                @endif
            </div>
        @endif
    </div>

    <div class="card rounded-4 lf-users-results-card">
        <div class="card-body p-3 p-md-4">
            <header class="d-flex align-items-center justify-content-between gap-2 mb-3">
                <h2 class="h5 m-0">Lista de membros</h2>
                <a href="{{ route('showNewUser') }}" class="btn btn-sm lf-users-add-icon"
                   title="Adicionar novo usuário" aria-label="Adicionar novo usuário">
                    <i class="bi bi-person-plus-fill" aria-hidden="true"></i>
                </a>
            </header>

            <x-livewire-tables::tools>
                <x-livewire-tables::tools.sorting-pills />
                <x-livewire-tables::tools.filter-pills />
            </x-livewire-tables::tools>
            <x-livewire-tables::table>
                <x-slot name="thead">
                    <x-livewire-tables::table.th.reorder />
                    <x-livewire-tables::table.th.bulk-actions />
                    <x-livewire-tables::table.th.row-contents />
                    @foreach($columns as $index => $column)
                        @continue($column->isHidden())
                        @continue($this->columnSelectIsEnabled() && !$this->columnSelectIsEnabledForColumn($column))
                        @continue($this->currentlyReorderingIsDisabled() && $column->isReorderColumn() && $this->hideReorderColumnUnlessReorderingIsEnabled())
                        <x-livewire-tables::table.th :column="$column" :index="$index" />
                    @endforeach
                </x-slot>
                @if($this->secondaryHeaderIsEnabled() && $this->hasColumnsWithSecondaryHeader())
                    <x-livewire-tables::table.tr.secondary-header :rows="$rows" />
                @endif
                <x-livewire-tables::table.tr.bulk-actions :rows="$rows" />
                @forelse($rows as $rowIndex => $row)
                    <x-livewire-tables::table.tr :row="$row" :rowIndex="$rowIndex">
                        <x-livewire-tables::table.td.reorder />
                        <x-livewire-tables::table.td.bulk-actions :row="$row" />
                        <x-livewire-tables::table.td.row-contents :rowIndex="$rowIndex" />
                        @foreach($columns as $colIndex => $column)
                            @continue($column->isHidden())
                            @continue($this->columnSelectIsEnabled() && !$this->columnSelectIsEnabledForColumn($column))
                            @continue($this->currentlyReorderingIsDisabled() && $column->isReorderColumn() && $this->hideReorderColumnUnlessReorderingIsEnabled())
                            <x-livewire-tables::table.td :column="$column" :colIndex="$colIndex">
                                {{ $column->renderContents($row) }}
                            </x-livewire-tables::table.td>
                        @endforeach
                    </x-livewire-tables::table.tr>
                    <x-livewire-tables::table.row-contents :row="$row" :rowIndex="$rowIndex" />
                @empty
                    <x-livewire-tables::table.empty />
                @endforelse
                @if($this->footerIsEnabled() && $this->hasColumnsWithFooter())
                    <x-slot name="tfoot">
                        @if($this->useHeaderAsFooterIsEnabled())
                            <x-livewire-tables::table.tr.secondary-header :rows="$rows" />
                        @else
                            <x-livewire-tables::table.tr.footer :rows="$rows" />
                        @endif
                    </x-slot>
                @endif
            </x-livewire-tables::table>

            <div class="lf-users-results-footer">
                <x-livewire-tables::pagination :rows="$rows" />
                <div class="lf-users-footer-tools d-flex flex-wrap gap-2 align-items-center mt-2">
                    @if($this->columnSelectIsEnabled())
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    id="lf-user-columns-trigger" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                    aria-expanded="false" aria-controls="lf-user-columns-menu">
                                Colunas
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-2 lf-users-columns-menu"
                                 id="lf-user-columns-menu" aria-labelledby="lf-user-columns-trigger">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="lf-all-columns"
                                           @checked($this->allDefaultVisibleColumnsAreSelected())
                                           wire:click="{{ $this->allDefaultVisibleColumnsAreSelected() ? 'deselectAllColumns' : 'selectAllColumns' }}"
                                           wire:loading.attr="disabled">
                                    <label class="form-check-label" for="lf-all-columns">Todas as colunas</label>
                                </div>
                                @foreach($this->getColumns() as $column)
                                    @if($column->isVisible() && $column->isSelectable())
                                        <div class="form-check" wire:key="lf-select-column-{{ $column->getSlug() }}">
                                            <input class="form-check-input" type="checkbox"
                                                   id="lf-column-{{ $column->getSlug() }}" wire:model="selectedColumns"
                                                   wire:loading.attr="disabled" value="{{ $column->getSlug() }}">
                                            <label class="form-check-label" for="lf-column-{{ $column->getSlug() }}">{{ $column->getTitle() }}</label>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($this->paginationIsEnabled() && $this->perPageVisibilityIsEnabled())
                        <label for="perPage" class="small text-muted mb-0">Por página</label>
                        <select wire:model="perPage" id="perPage" class="form-select form-select-sm lf-users-perpage">
                            @foreach($this->getPerPageAccepted() as $item)
                                <option value="{{ $item }}" wire:key="lf-per-page-{{ $item }}">
                                    {{ $item === -1 ? __('All') : $item }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @isset($customView)
        @include($customView)
    @endisset
</x-livewire-tables::wrapper>

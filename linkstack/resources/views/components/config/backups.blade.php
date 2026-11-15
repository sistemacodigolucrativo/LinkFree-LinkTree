<div class="backup-list">
<style>
    .backup-list button {
        border-style: none;
        background-color: #0085ff;
        color: #fff;
        border-radius: 5px;
    }

    .backup-list button:hover {
        background-color: #0065c1;
        color: #FFF;
        box-shadow: 0 10px 20px -10px rgba(0, 0, 0, 0.6);
    }

    .backup-list .buttondm {
        display: inline-block;
        text-decoration: none;
        min-height:48px; height:auto; padding:12px;
        text-align: center;
        vertical-align: middle;
        font-size: 18px;
        width:100%; max-width:300px; box-sizing:border-box; overflow-wrap:anywhere;
        font-weight: 700;
        line-min-height:48px; height:auto; padding:12px;
        letter-spacing: .1px;
        white-space: wrap;
        border-radius: 8px;
        cursor: pointer
    }

    .backup-list .button-hover, .backup-list .credit-hover {
        display: inline-block;
        -webkit-transform: perspective(1px) translateZ(0);
        transform: perspective(1px) translateZ(0);
        box-shadow: 0 0 1px rgba(0, 0, 0, 0);
        -webkit-transition-duration: .3s;
        transition-duration: .3s;
        -webkit-transition-property: transform;
        transition-property: transform
    }

    .backup-list .button-hover:active, .backup-list .credit-hover:active, .backup-list .button-hover:focus, .backup-list .credit-hover:focus, .backup-list .button-hover:hover, .backup-list .credit-hover:hover {
        -webkit-transform: scale(1);
        transform: scale(1)
    }

    .backup-list .container {
        align-items: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: auto;
        width: 100%
    }
</style>
<!-- Custom icons font-awesome -->
<script src="{{ asset('assets/external-dependencies/fontawesome.js') }}" crossorigin="anonymous"></script>
@if (file_exists(base_path('backups/updater-backups/')) and is_dir(base_path('backups/updater-backups/')))


    <div class="container">
        <br><br>
        <h3>{{ __('messages.Download your updater backups:') }}</h3>
        <hp>{{ __('messages.The server will never store more that two backups at a time') }}</hp><br><br><br>
        <?php
        $test = 'test';
        if ($handle = opendir('backups/updater-backups')) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..') {
                    $entrys = substr($entry, 17);
                    echo '<div class="button-entrance"><a class="buttondm button-hover icon-hover" style="color:#ffffff; background-color:#000;" href="' . url('admin/backups') . '?file=' . $entry . '"><i style="color: " class="icon hvr-icon fa fa-download"></i>&nbsp; ';
                    print_r($entrys);
                    echo '</a></div><br>';
                }
            }
        } ?>
    </div>
@else
    <div class="container">
        <h3>{{ __('messages.No backups found') }}</h3>
    </div>
@endif
<center><a data-toggle="tab" href="#3" class="btn btn-primary"><i
                class="fa-solid fa-floppy-disk"></i> {{ __('messages.Backup your instance') }}</a></center>

</div>

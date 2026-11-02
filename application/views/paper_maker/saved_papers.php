<section class="panel">
    <header class="panel-heading">
        <h4 class="panel-title"><i class="fas fa-folder-open"></i> <?= translate('saved_papers'); ?></h4>
    </header>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th><?= translate('title'); ?></th>
                        <th><?= translate('class'); ?></th>
                        <th><?= translate('subject'); ?></th>
                        <th><?= translate('total_marks'); ?></th>
                        <th><?= translate('mode'); ?></th>
                        <th><?= translate('created'); ?></th>
                        <th><?= translate('action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($papers as $p): ?>
                        <tr>
                            <td><?= html_escape($p->paper_title); ?></td>
                            <td><?= html_escape($p->class_name ?? $p->class_id); ?></td>
                            <td><?= html_escape($p->subject_name ?? $p->subject_id); ?></td>
                            <td><?= $p->total_marks; ?></td>
                            <td><span class="label label-<?= $p->generation_mode === 'auto' ? 'info' : 'default'; ?>"><?= ucfirst($p->generation_mode); ?></span></td>
                            <td><?= date('d M Y, h:i A', strtotime($p->created_at)); ?></td>
                            <td>
                                <a href="<?= base_url('paper_maker/preview/' . $p->id); ?>" class="btn btn-xs btn-primary"><?= translate('preview'); ?></a>
                                <a href="<?= base_url('paper_maker/export_pdf/' . $p->id . '/paper'); ?>" class="btn btn-xs btn-default"><?= translate('pdf'); ?></a>
                                <?php if (get_permission('paper_maker_generator', 'is_delete')): ?>
                                <a href="<?= base_url('paper_maker/delete_paper/' . $p->id); ?>" class="btn btn-xs btn-danger"
                                   onclick="return confirm('<?= translate('confirm_delete'); ?>');"><?= translate('delete'); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($papers)): ?>
                        <tr><td colspan="7" class="text-center text-muted"><?= translate('no_papers_generated_yet'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

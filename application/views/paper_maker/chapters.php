<section class="panel"><header class="panel-heading"><h4 class="panel-title"><i class="fas fa-book"></i> Chapters</h4></header><div class="panel-body">
<button class="btn btn-primary mb-md" data-toggle="modal" data-target="#chapterModal" id="btnAddChapter"><i class="fas fa-plus"></i> Add Chapter</button>
<div class="row mb-sm">
    <div class="col-md-3">
        <select id="filterChapterClass" class="form-control">
            <option value="">All Classes</option>
            <?php foreach ($classes as $c): ?><option value="<?= $c->id ?>"><?= html_escape($c->name) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select id="filterChapterSubject" class="form-control">
            <option value="">All Subjects</option>
            <?php foreach ($subjects as $s): ?><option value="<?= $s->id ?>"><?= html_escape($s->name) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <button class="btn btn-default btn-block" id="btnApplyChapterFilters">Apply Filters</button>
    </div>
</div>
<div class="table-responsive"><table class="table table-bordered table-hover"><thead><tr><th>Class</th><th>Subject</th><th>Chapter Name</th><th>Action</th></tr></thead><tbody id="chaptersTableBody">
<?php foreach($chapters as $ch): ?><tr data-class-id="<?= (int)$ch->class_id ?>" data-subject-id="<?= (int)$ch->subject_id ?>"><td><?= html_escape($ch->class_name ?? $ch->class_id) ?></td><td><?= html_escape($ch->subject_name ?? $ch->subject_id) ?></td><td><?= html_escape($ch->chapter_name) ?></td><td><button class="btn btn-xs btn-danger btnDeleteChapter" data-id="<?= $ch->id ?>">Delete</button></td></tr><?php endforeach; ?>
</tbody></table>
<p id="noChapterResults" class="text-muted text-center" style="display:none;">No chapters match the selected filters.</p>
</div></div></section>
<div class="modal fade" id="chapterModal"><div class="modal-dialog"><div class="modal-content"><form id="chapterForm"><div class="modal-header"><h4 class="modal-title">Add Chapter</h4></div><div class="modal-body"><div class="form-group"><label>Class</label><select id="chapter_class_id" class="form-control" required><option value="">Select Class</option><?php foreach($classes as $c): ?><option value="<?= $c->id ?>"><?= html_escape($c->name) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Subject</label><select id="chapter_subject_id" class="form-control" required><option value="">Select Subject</option></select></div><div class="form-group"><label>Chapter Name</label><input id="chapter_name" class="form-control" required></div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Chapter</button></div></form></div></div></div>
<script>const PM_BASE_URL='<?= base_url('paper_maker'); ?>';</script>

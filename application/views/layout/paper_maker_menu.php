<?php if (moduleIsEnabled('paper_maker')): ?>
<li class="nav-parent <?php if ($main_menu == 'paper_maker') echo 'nav-expanded nav-active'; ?>">
    <a>
        <i class="fas fa-file-alt"></i><span><?= translate('paper_maker'); ?></span>
    </a>
    <ul class="nav nav-children">
        <?php if (get_permission('paper_maker_question_bank', 'is_view')): ?>
        <li class="<?php if ($sub_page == 'paper_maker/question_bank') echo 'nav-active'; ?>">
            <a href="<?= base_url('paper_maker/question_bank'); ?>">
                <span><i class="fas fa-caret-right"></i><?= translate('question_bank'); ?></span>
            </a>
        </li>
        <?php endif; ?>
        <?php if (get_permission('paper_maker_question_bank', 'is_view')): ?>
        <li class="<?php if ($sub_page == 'paper_maker/chapters') echo 'nav-active'; ?>">
            <a href="<?= base_url('paper_maker/chapters'); ?>"><span><i class="fas fa-caret-right"></i>Chapters</span></a>
        </li>
        <?php endif; ?>
        <?php if (get_permission('paper_maker_generator', 'is_add')): ?>
        <li class="<?php if ($sub_page == 'paper_maker/auto_generator') echo 'nav-active'; ?>">
            <a href="<?= base_url('paper_maker/auto_generate'); ?>">
                <span><i class="fas fa-caret-right"></i><?= translate('auto_generator'); ?></span>
            </a>
        </li>
        <li class="<?php if ($sub_page == 'paper_maker/manual_create') echo 'nav-active'; ?>">
            <a href="<?= base_url('paper_maker/manual_create'); ?>">
                <span><i class="fas fa-caret-right"></i><?= translate('manual_paper_builder'); ?></span>
            </a>
        </li>
        <?php endif; ?>
        <?php if (get_permission('paper_maker_generator', 'is_view')): ?>
        <li class="<?php if ($sub_page == 'paper_maker/saved_papers') echo 'nav-active'; ?>">
            <a href="<?= base_url('paper_maker/saved_papers'); ?>">
                <span><i class="fas fa-caret-right"></i><?= translate('saved_papers'); ?></span>
            </a>
        </li>
        <?php endif; ?>
        <?php if (get_permission('paper_maker_generator', 'is_view') || get_permission('paper_maker_generator', 'is_add')): ?>
        <li class="<?php if ($sub_page == 'paper_maker/templates') echo 'nav-active'; ?>">
            <a href="<?= base_url('paper_maker/templates'); ?>">
                <span><i class="fas fa-caret-right"></i><?= translate('paper_templates'); ?></span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</li>
<?php endif; ?>

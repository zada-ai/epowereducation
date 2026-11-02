$(function(){
 $('#chapter_class_id').on('change',function(){var id=$(this).val(); if(!id)return; $.post(PM_BASE_URL + '/get_subjects_by_class', $.extend({class_id:id},csrfData), function(res){ if(res.status!=='success') return; var $s=$('#chapter_subject_id').html('<option value="">Select Subject</option>'); res.subjects.forEach(function(x){$s.append($('<option>').val(x.id).text(x.name));}); }, 'json');});
 $('#chapterForm').on('submit',function(e){e.preventDefault(); $.post(PM_BASE_URL+'/save_chapter',$.extend({class_id:$('#chapter_class_id').val(),subject_id:$('#chapter_subject_id').val(),chapter_name:$('#chapter_name').val()},csrfData),function(r){if(r.status==='success')location.reload();else alert(r.message||'Save failed');},'json');});
 $(document).on('click','.btnDeleteChapter',function(){if(!confirm('Delete this chapter?'))return; $.post(PM_BASE_URL+'/delete_chapter/'+$(this).data('id'),csrfData,function(r){if(r.status==='success')location.reload();},'json');});

 function applyChapterFilters(){
     var classId = $('#filterChapterClass').val();
     var subjectId = $('#filterChapterSubject').val();
     var visibleCount = 0;
     $('#chaptersTableBody tr').each(function(){
         var $row = $(this);
         var matchesClass = !classId || String($row.data('class-id')) === String(classId);
         var matchesSubject = !subjectId || String($row.data('subject-id')) === String(subjectId);
         var show = matchesClass && matchesSubject;
         $row.toggle(show);
         if (show) visibleCount++;
     });
     $('#noChapterResults').toggle(visibleCount === 0);
 }
 $('#btnApplyChapterFilters').on('click', applyChapterFilters);
});

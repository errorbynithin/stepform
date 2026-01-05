(function($){
  function collectFields(form){
    const data = [];
    const formData = new FormData(form);
    form.querySelectorAll('[name]').forEach(input => {
      const name = input.getAttribute('name');
      if (input.type === 'file') {
        return;
      }
      if (input.type === 'checkbox') {
        const values = formData.getAll(name);
        data.push({name, label: input.closest('.psfb-field')?.querySelector('label')?.textContent || name, value: values});
      } else if (input.type === 'radio') {
        if (input.checked) {
          data.push({name, label: input.closest('.psfb-field')?.querySelector('label')?.textContent || name, value: input.value});
        }
      } else {
        data.push({name, label: input.closest('.psfb-field')?.querySelector('label')?.textContent || name, value: formData.get(name)});
      }
    });
    return data;
  }

  function handleSteps($container){
    const steps = $container.find('.psfb-step');
    let current = 0;
    const update = () => {
      steps.hide().eq(current).show();
      $container.find('.psfb-step-list li').removeClass('active').eq(current).addClass('active');
    };
    $container.on('click', '.psfb-next', function(){
      if (current < steps.length-1) {current++; update();}
    });
    $container.on('click', '.psfb-prev', function(){
      if (current > 0) {current--; update();}
    });
    update();
  }

  function handleSubmit($container){
    const form = $container.find('form.psfb-form')[0];
    if (!form) return;
    form.addEventListener('submit', function(e){
      e.preventDefault();
      const formId = $container.data('form-id');
      const data = collectFields(form);
      const fd = new FormData(form);
      fd.append('action','psfb_submit_entry');
      fd.append('nonce', PSFB_FRONTEND.nonce);
      fd.append('form_id', formId);
      fd.append('payload', JSON.stringify(data));

      fetch(PSFB_FRONTEND.ajaxUrl, {method:'POST', body: fd, credentials:'same-origin'})
        .then(r=>r.json())
        .then(resp => {
          if (resp.success) {
            form.reset();
            $container.find('.psfb-step').hide().first().show();
            alert('Submitted successfully');
          } else {
            alert(resp.data && resp.data.message ? resp.data.message : 'Submission error');
          }
        }).catch(() => alert('Submission error'));
    });
  }

  $(function(){
    $('.psfb-frontend').each(function(){
      const $this = $(this);
      handleSteps($this);
      handleSubmit($this);
    });
  });
})(jQuery);

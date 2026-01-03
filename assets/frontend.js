(function(){
  function qs(el, selector){ return el.querySelector(selector); }
  function qsa(el, selector){ return Array.from(el.querySelectorAll(selector)); }

  document.addEventListener('DOMContentLoaded', () => {
    qsa(document, '.stepform').forEach(initForm);
  });

  function initForm(root){
    const config = JSON.parse(root.dataset.config || '{}');
    const steps = config.steps || [];
    let current = 0;

    const body = qs(root, '.stepform-body');
    const fieldsWrap = qs(root, '.stepform-fields');
    const title = qs(root, '.step-title');
    const desc = qs(root, '.step-description');
    const progress = qs(root, '.step-progress .bar');
    const prevBtn = qs(root, '.stepform-prev');
    const nextBtn = qs(root, '.stepform-next');
    const submitBtn = qs(root, '.stepform-submit');
    const formEl = qs(root, '.stepform-form');

    function renderStep(){
      const step = steps[current];
      if(!step) return;

      title.textContent = step.title || 'Step';
      desc.textContent = step.description || '';
      const pct = ((current+1)/steps.length)*100;
      progress.style.width = pct + '%';

      fieldsWrap.innerHTML = '';
      (step.fields || []).forEach(field => {
        const wrap = document.createElement('div');
        wrap.className = 'sf-front-field';
        const label = document.createElement('label');
        label.textContent = field.label || 'Field';
        if(field.required){
          const req = document.createElement('span');
          req.textContent = ' *';
          req.className = 'req';
          label.appendChild(req);
        }
        const input = document.createElement(field.type === 'textarea' ? 'textarea' : 'input');
        if(field.type !== 'textarea'){
          input.type = field.type || 'text';
        }
        input.name = field.id;
        input.placeholder = field.placeholder || '';
        if(field.readonly){
          input.readOnly = true;
        }
        wrap.appendChild(label);
        wrap.appendChild(input);
        if(field.help){
          const help = document.createElement('small');
          help.textContent = field.help;
          wrap.appendChild(help);
        }
        fieldsWrap.appendChild(wrap);
      });

      prevBtn.style.display = current === 0 ? 'none' : 'inline-flex';
      nextBtn.style.display = current === steps.length - 1 ? 'none' : 'inline-flex';
      submitBtn.style.display = current === steps.length - 1 ? 'inline-flex' : 'none';
    }

    function validateStep(){
      const step = steps[current];
      if(!step) return true;
      let valid = true;
      (step.fields || []).forEach(field => {
        if(!field.required) return;
        const input = formEl.querySelector(`[name="${field.id}"]`);
        if(input && !input.value.trim()){
          input.classList.add('sf-error');
          valid = false;
        } else if(input){
          input.classList.remove('sf-error');
        }
      });
      return valid;
    }

    prevBtn.addEventListener('click', () => {
      if(current > 0){
        current -= 1;
        renderStep();
      }
    });

    nextBtn.addEventListener('click', () => {
      if(!validateStep()) return;
      if(current < steps.length - 1){
        current += 1;
        renderStep();
      }
    });

    formEl.addEventListener('submit', async (e) => {
      e.preventDefault();
      if(!validateStep()) return;

      const data = new FormData(formEl);
      const payload = { fields: {} };
      for (const [key, value] of data.entries()) {
        payload.fields[key] = value;
      }

      const notice = document.createElement('div');
      notice.className = 'sf-notice';
      notice.textContent = 'Submitting...';
      formEl.appendChild(notice);

      try {
        const res = await fetch(`${STEPFORM_FRONTEND.restUrl}/forms/${root.dataset.form}/submit`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': STEPFORM_FRONTEND.nonce
          },
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        notice.textContent = json.message || 'Thanks!';
        if(res.ok){
          formEl.reset();
          current = 0;
          renderStep();
        }
      } catch(err){
        notice.textContent = 'Something went wrong. Please try again.';
      }
    });

    renderStep();
  }
})();

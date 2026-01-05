(function(){
  const root = document.getElementById('psfb-builder-root');
  if (root) {
    const existing = root.dataset.form ? JSON.parse(root.dataset.form) : null;
    const state = {
      id: existing && existing.id ? existing.id : 0,
      title: existing && existing.title ? existing.title : 'New Form',
      builder: existing && existing.builder ? JSON.parse(existing.builder) : {steps: [{title: 'Step 1', fields: []}]},
      settings: existing && existing.settings ? JSON.parse(existing.settings) : {notifications: {}, admin_notification: {enabled: true}, user_notification: {enabled: false}}
    };

    const el = (tag, attrs = {}, children = []) => {
      const n = document.createElement(tag);
      Object.entries(attrs).forEach(([k,v]) => {
        if (k === 'class') n.className = v; else if (k === 'text') n.textContent = v; else n.setAttribute(k,v);
      });
      (Array.isArray(children) ? children : [children]).forEach(c => { if (!c) return; n.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);});
      return n;
    };

    const saveNotice = el('div', {class: 'psfb-notice'});

    const renderFieldLibrary = () => {
      const container = el('div', {class: 'psfb-panel'});
      container.appendChild(el('h3', {text: 'Fields'}));
      PSFB_DATA.fieldLibrary.forEach(field => {
        const btn = el('button', {class: 'button psfb-field-btn', type:'button'} , [el('span', {class:'dashicons '+field.icon}), ' ', field.label]);
        btn.addEventListener('click', () => {
          const currentStep = state.builder.steps[state.activeStep || 0];
          currentStep.fields.push({type: field.type, label: field.label, required: false, name: field.type + '-' + Date.now(), placeholder: ''});
          render();
        });
        container.appendChild(btn);
      });
      return container;
    };

    const renderSteps = () => {
      const wrapper = el('div', {class: 'psfb-panel'});
      wrapper.appendChild(el('h3', {text: 'Steps'}));
      const list = el('ul', {class: 'psfb-step-manager'});
      state.builder.steps.forEach((step, idx) => {
        const row = el('li', {class: state.activeStep === idx ? 'active' : ''});
        const input = el('input', {value: step.title || 'Step '+(idx+1)});
        input.addEventListener('input', e => {step.title = e.target.value; list.querySelectorAll('li')[idx].dataset.title = step.title;});
        const select = el('button', {type:'button', class:'button-link'}, 'Open');
        select.addEventListener('click', () => {state.activeStep = idx; render();});
        const up = el('button', {type:'button', class:'button-link dashicons dashicons-arrow-up'});
        up.addEventListener('click', ()=>{ if (idx>0){ const tmp = state.builder.steps[idx-1]; state.builder.steps[idx-1]=step; state.builder.steps[idx]=tmp; state.activeStep=idx-1; render(); }});
        const down = el('button', {type:'button', class:'button-link dashicons dashicons-arrow-down'});
        down.addEventListener('click', ()=>{ if (idx < state.builder.steps.length-1){ const tmp = state.builder.steps[idx+1]; state.builder.steps[idx+1]=step; state.builder.steps[idx]=tmp; state.activeStep=idx+1; render(); }});
        const del = el('button', {type:'button', class:'button-link dashicons dashicons-trash'});
        del.addEventListener('click', ()=>{ if (state.builder.steps.length>1){ state.builder.steps.splice(idx,1); state.activeStep=0; render(); }});
        row.appendChild(input);
        row.appendChild(select);
        row.appendChild(up);
        row.appendChild(down);
        row.appendChild(del);
        list.appendChild(row);
      });
      const add = el('button', {class:'button', type:'button'}, '+ Add Step');
      add.addEventListener('click', ()=>{ state.builder.steps.push({title: 'Step '+ (state.builder.steps.length+1), fields: []}); state.activeStep = state.builder.steps.length-1; render(); });
      wrapper.appendChild(list);
      wrapper.appendChild(add);
      return wrapper;
    };

    let selectedField = null;

    const renderFieldsCanvas = () => {
      const step = state.builder.steps[state.activeStep || 0];
      const canvas = el('div', {class: 'psfb-canvas'});
      canvas.appendChild(el('h3', {text: step.title || 'Step'}));
      if (!step.fields.length) {
        canvas.appendChild(el('p', {class:'description', text:'Drag or click fields to add them.'}));
      }
      step.fields.forEach((field, idx) => {
        const card = el('div', {class: 'psfb-field-card' + (selectedField === field ? ' selected' : '')});
        card.appendChild(el('strong', {text: field.label || field.type}));
        card.appendChild(el('span', {class:'psfb-meta', text: field.type}));
        const controls = el('div', {class:'psfb-card-controls'});
        const up = el('button', {type:'button', class:'button-link dashicons dashicons-arrow-up-alt2'});
        up.addEventListener('click', ()=>{ if (idx>0){ const tmp=step.fields[idx-1]; step.fields[idx-1]=field; step.fields[idx]=tmp; render(); }});
        const down = el('button', {type:'button', class:'button-link dashicons dashicons-arrow-down-alt2'});
        down.addEventListener('click', ()=>{ if (idx < step.fields.length-1){ const tmp=step.fields[idx+1]; step.fields[idx+1]=field; step.fields[idx]=tmp; render(); }});
        const del = el('button', {type:'button', class:'button-link dashicons dashicons-trash'});
        del.addEventListener('click', ()=>{ step.fields.splice(idx,1); if (selectedField===field) selectedField=null; render(); });
        controls.append(up, down, del);
        card.appendChild(controls);
        card.addEventListener('click', ()=>{ selectedField = field; render(); });
        canvas.appendChild(card);
      });
      return canvas;
    };

    const renderFieldSettings = () => {
      const panel = el('div', {class:'psfb-panel settings'});
      panel.appendChild(el('h3', {text:'Field Settings'}));
      if (!selectedField) {
        panel.appendChild(el('p', {text:'Select a field to edit settings.'}));
        return panel;
      }
      const addControl = (label, inputEl) => {
        const row = el('label', {class:'psfb-setting-row'}, [el('span', {text: label}), inputEl]);
        panel.appendChild(row);
      };

      const labelInput = el('input', {value: selectedField.label || ''});
      labelInput.addEventListener('input', e => {selectedField.label = e.target.value; render();});
      addControl('Label', labelInput);

      const nameInput = el('input', {value: selectedField.name || ''});
      nameInput.addEventListener('input', e => {selectedField.name = e.target.value;});
      addControl('Name', nameInput);

      const ph = el('input', {value: selectedField.placeholder || ''});
      ph.addEventListener('input', e => {selectedField.placeholder = e.target.value;});
      addControl('Placeholder', ph);

      const req = el('input', {type:'checkbox'});
      req.checked = !!selectedField.required;
      req.addEventListener('change', e => {selectedField.required = e.target.checked;});
      addControl('Required', req);

      const logic = el('textarea', {placeholder:'Conditional logic rules as JSON'});
      logic.value = selectedField.logic ? JSON.stringify(selectedField.logic) : '';
      logic.addEventListener('input', e => {try { selectedField.logic = JSON.parse(e.target.value || '[]'); logic.classList.remove('error'); } catch(err){logic.classList.add('error'); }});
      addControl('Conditional Logic', logic);

      if (['select','radio','checkbox'].includes(selectedField.type)) {
        const opts = el('textarea', {placeholder:'One option per line (Label|Value)'});
        const optionLines = (selectedField.options || []).map(opt => (opt.label||'') + '|' + (opt.value||''));
        opts.value = optionLines.join('\n');
        opts.addEventListener('input', e => {
          const options = e.target.value.split(/\n/).filter(Boolean).map(line => {
            const parts = line.split('|');
            return {label: parts[0], value: parts[1] || parts[0]};
          });
          selectedField.options = options;
        });
        addControl('Options', opts);
      }

      if (['number'].includes(selectedField.type)) {
        const min = el('input', {type:'number', value: selectedField.min || ''});
        min.addEventListener('input', e => {selectedField.min = e.target.value;});
        addControl('Min', min);
        const max = el('input', {type:'number', value: selectedField.max || ''});
        max.addEventListener('input', e => {selectedField.max = e.target.value;});
        addControl('Max', max);
        const step = el('input', {type:'number', value: selectedField.step || ''});
        step.addEventListener('input', e => {selectedField.step = e.target.value;});
        addControl('Step', step);
      }

      return panel;
    };

    const renderSettings = () => {
      const panel = el('div', {class:'psfb-panel notifications'});
      panel.appendChild(el('h3', {text:'Notifications'}));

      const adminEnabled = el('input', {type:'checkbox'});
      adminEnabled.checked = !!state.settings.admin_notification?.enabled;
      adminEnabled.addEventListener('change', e => {state.settings.admin_notification = state.settings.admin_notification || {}; state.settings.admin_notification.enabled = e.target.checked;});
      panel.appendChild(el('label', {}, [adminEnabled, ' Admin Email Notification']));

      const adminTo = el('input', {value: state.settings.admin_notification?.to || ''});
      adminTo.addEventListener('input', e => {state.settings.admin_notification = state.settings.admin_notification || {}; state.settings.admin_notification.to = e.target.value;});
      panel.appendChild(el('label', {class:'psfb-setting-row'}, [el('span',{text:'Admin To'}), adminTo]));

      const userEnabled = el('input', {type:'checkbox'});
      userEnabled.checked = !!state.settings.user_notification?.enabled;
      userEnabled.addEventListener('change', e => {state.settings.user_notification = state.settings.user_notification || {}; state.settings.user_notification.enabled = e.target.checked;});
      panel.appendChild(el('label', {}, [userEnabled, ' User Email Notification']));

      const userSubj = el('input', {value: state.settings.user_notification?.subject || ''});
      userSubj.addEventListener('input', e => {state.settings.user_notification = state.settings.user_notification || {}; state.settings.user_notification.subject = e.target.value;});
      panel.appendChild(el('label', {class:'psfb-setting-row'}, [el('span',{text:'User Subject'}), userSubj]));

      const body = el('textarea', {placeholder:'Use [all_fields] to include all fields'});
      body.value = state.settings.user_notification?.body || '';
      body.addEventListener('input', e => {state.settings.user_notification = state.settings.user_notification || {}; state.settings.user_notification.body = e.target.value;});
      panel.appendChild(el('label', {class:'psfb-setting-row'}, [el('span',{text:'User Body'}), body]));

      return panel;
    };

    const saveForm = () => {
      saveNotice.textContent = 'Saving...';
      const payload = new FormData();
      payload.append('action','psfb_save_form');
      payload.append('nonce', PSFB_DATA.nonce);
      payload.append('form_id', state.id);
      payload.append('title', state.title || 'Untitled Form');
      payload.append('builder', JSON.stringify(state.builder));
      payload.append('settings', JSON.stringify(state.settings));

      fetch(PSFB_DATA.ajaxUrl, {method:'POST', credentials:'same-origin', body: payload})
        .then(r=>r.json())
        .then(data => {
          if (data.success) {
            state.id = data.data.id;
            saveNotice.textContent = 'Saved';
            const url = new URL(window.location.href);
            url.searchParams.set('form_id', state.id);
            window.history.replaceState({}, '', url);
          } else {
            saveNotice.textContent = data.data && data.data.message ? data.data.message : 'Error saving';
          }
        }).catch(()=>{ saveNotice.textContent = 'Error saving'; });
    };

    const render = () => {
      root.innerHTML = '';
      const header = el('div', {class:'psfb-builder-header'});
      const titleInput = el('input', {value: state.title, class:'psfb-title'});
      titleInput.addEventListener('input', e => {state.title = e.target.value;});
      const saveBtn = el('button', {class:'button button-primary'}, 'Save Form');
      saveBtn.addEventListener('click', saveForm);
      header.append(titleInput, saveBtn, saveNotice);

      const layout = el('div', {class:'psfb-builder'});
      const left = el('div', {class:'psfb-left'});
      left.append(renderFieldLibrary(), renderSteps());
      const middle = el('div', {class:'psfb-middle'});
      middle.appendChild(renderFieldsCanvas());
      const right = el('div', {class:'psfb-right'});
      right.append(renderFieldSettings(), renderSettings());

      layout.append(left, middle, right);
      root.append(header, layout);
    };

    state.activeStep = 0;
    render();
  }

  // Submission modal on table
  const modal = document.getElementById('psfb-entry-modal');
  if (modal) {
    const pre = modal.querySelector('.psfb-entry-pre');
    document.querySelectorAll('.psfb-view-entry').forEach(btn => {
      btn.addEventListener('click', () => {
        const entry = JSON.parse(btn.dataset.entry);
        pre.textContent = JSON.stringify(JSON.parse(entry.data || '{}'), null, 2);
        modal.style.display = 'block';
      });
    });
    modal.querySelector('.psfb-close').addEventListener('click', () => {modal.style.display='none';});
  }
})();

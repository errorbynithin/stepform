(function(){
  const root = document.getElementById('stepform-builder');
  if(!root){
    return;
  }

  const hidden = document.getElementById('stepform-config-input');
  const state = JSON.parse(root.dataset.config || '{}');

  const dom = {
    sidebar: null,
    stage: null,
    settings: null
  };

  function saveState(){
    hidden.value = JSON.stringify(state);
  }

  function selectStep(index){
    state.activeStep = index;
    render();
  }

  function selectField(fieldId){
    state.activeField = fieldId;
    render();
  }

  function addStep(){
    state.steps = state.steps || [];
    state.steps.push({
      id: 'step_' + Date.now(),
      title: 'New Step',
      description: 'Describe this step',
      fields: []
    });
    selectStep(state.steps.length - 1);
    saveState();
  }

  function addField(type){
    const step = state.steps[state.activeStep];
    step.fields.push({
      id: 'field_' + Date.now(),
      type,
      label: 'Field Label',
      placeholder: 'Placeholder',
      required: false,
      help: ''
    });
    state.activeField = step.fields[step.fields.length - 1].id;
    saveState();
    render();
  }

  function updateField(fieldId, key, value){
    const step = state.steps[state.activeStep];
    const field = step.fields.find(f => f.id === fieldId);
    if(!field) return;
    field[key] = value;
    saveState();
    render();
  }

  function buildSidebar(){
    const sidebar = document.createElement('div');
    sidebar.className = 'sf-sidebar';

    const stepsList = document.createElement('div');
    stepsList.className = 'sf-steps';

    (state.steps || []).forEach((step, index) => {
      const item = document.createElement('button');
      item.type = 'button';
      item.className = 'sf-step' + (index === state.activeStep ? ' active' : '');
      item.innerHTML = '<span class="num">'+(index+1)+'</span> ' + step.title;
      item.addEventListener('click', () => selectStep(index));
      stepsList.appendChild(item);
    });

    const addStepBtn = document.createElement('button');
    addStepBtn.type = 'button';
    addStepBtn.className = 'button sf-add-step';
    addStepBtn.textContent = STEPFORM_ADMIN?.texts?.addStep || 'Add Step';
    addStepBtn.addEventListener('click', addStep);

    sidebar.appendChild(stepsList);
    sidebar.appendChild(addStepBtn);
    return sidebar;
  }

  function buildStage(){
    const wrap = document.createElement('div');
    wrap.className = 'sf-stage';
    const step = (state.steps || [])[state.activeStep] || {};

    const title = document.createElement('h2');
    title.textContent = step.title || 'Step';
    const desc = document.createElement('p');
    desc.textContent = step.description || '';

    const fields = document.createElement('div');
    fields.className = 'sf-fields';

    (step.fields || []).forEach(field => {
      const card = document.createElement('div');
      card.className = 'sf-field-card' + (field.id === state.activeField ? ' active' : '');
      card.addEventListener('click', () => selectField(field.id));

      const label = document.createElement('div');
      label.className = 'sf-field-label';
      label.innerHTML = `${field.label || 'Field'} ${field.required ? '<span class="req">*</span>' : ''}`;

      const input = document.createElement(field.type === 'textarea' ? 'textarea' : 'input');
      if(field.type !== 'textarea'){
        input.type = field.type || 'text';
      }
      input.placeholder = field.placeholder || '';
      input.disabled = true;
      card.appendChild(label);
      card.appendChild(input);

      if(field.help){
        const help = document.createElement('small');
        help.className = 'sf-help';
        help.textContent = field.help;
        card.appendChild(help);
      }

      fields.appendChild(card);
    });

    const addPanel = document.createElement('div');
    addPanel.className = 'sf-add-panel';
    const addTextBtn = document.createElement('button');
    addTextBtn.type = 'button';
    addTextBtn.className = 'button';
    addTextBtn.textContent = 'Add Text Field';
    addTextBtn.addEventListener('click', () => addField('text'));

    const addEmailBtn = document.createElement('button');
    addEmailBtn.type = 'button';
    addEmailBtn.className = 'button';
    addEmailBtn.textContent = 'Add Email Field';
    addEmailBtn.addEventListener('click', () => addField('email'));

    const addTextareaBtn = document.createElement('button');
    addTextareaBtn.type = 'button';
    addTextareaBtn.className = 'button';
    addTextareaBtn.textContent = 'Add Textarea Field';
    addTextareaBtn.addEventListener('click', () => addField('textarea'));

    addPanel.appendChild(addTextBtn);
    addPanel.appendChild(addEmailBtn);
    addPanel.appendChild(addTextareaBtn);

    wrap.appendChild(title);
    wrap.appendChild(desc);
    wrap.appendChild(fields);
    wrap.appendChild(addPanel);
    return wrap;
  }

  function buildSettings(){
    const aside = document.createElement('div');
    aside.className = 'sf-settings';
    const step = (state.steps || [])[state.activeStep] || {};
    const field = (step.fields || []).find(f => f.id === state.activeField);

    if(!field){
      aside.innerHTML = '<p class="sf-empty">Select a field to edit settings.</p>';
      return aside;
    }

    const form = document.createElement('div');
    form.className = 'sf-settings-form';

    const controls = [
      { key: 'label', label: 'Field Label', type: 'text' },
      { key: 'placeholder', label: 'Placeholder Text', type: 'text' },
      { key: 'help', label: 'Help Text', type: 'textarea' },
    ];

    controls.forEach(ctrl => {
      const group = document.createElement('label');
      group.className = 'sf-control';
      group.innerHTML = `<span>${ctrl.label}</span>`;
      const input = document.createElement(ctrl.type === 'textarea' ? 'textarea' : 'input');
      if(ctrl.type !== 'textarea'){
        input.type = ctrl.type;
      }
      input.value = field[ctrl.key] || '';
      input.addEventListener('input', (e) => updateField(field.id, ctrl.key, e.target.value));
      group.appendChild(input);
      form.appendChild(group);
    });

    const requiredToggle = document.createElement('label');
    requiredToggle.className = 'sf-toggle';
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.checked = !!field.required;
    checkbox.addEventListener('change', e => updateField(field.id, 'required', e.target.checked));
    requiredToggle.appendChild(checkbox);
    requiredToggle.append(' Required field');

    form.appendChild(requiredToggle);

    aside.appendChild(form);
    return aside;
  }

  function render(){
    root.innerHTML = '';
    root.className = 'sf-builder-shell';
    state.activeStep = typeof state.activeStep === 'number' ? state.activeStep : 0;
    const step = (state.steps || [])[state.activeStep];
    if(step && !state.activeField && step.fields && step.fields[0]){
      state.activeField = step.fields[0].id;
    }

    const layout = document.createElement('div');
    layout.className = 'sf-grid';
    layout.appendChild(buildSidebar());
    layout.appendChild(buildStage());
    layout.appendChild(buildSettings());
    root.appendChild(layout);
    saveState();
  }

  render();
})();

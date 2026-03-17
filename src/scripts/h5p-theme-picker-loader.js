import 'h5p-theme-picker';

(() => {
  const pickerValuesField = document.querySelector('#picker_values');
  if (!pickerValuesField) {
    console.error('Picker values field not found');
    return;
  }

  const picker = document.querySelector('h5p-theme-picker');
  if (!picker) {
    console.error('H5P Theme Picker not found');
    return;
  }

  picker.addEventListener('theme-change', (event) => {
    const themeValues = event.detail;
    let jsonString;
    try {
      jsonString = JSON.stringify(themeValues);
    } catch (error) {
      console.error('Failed to serialize theme values:', error);
      return;
    }

    pickerValuesField.value = jsonString;
  });
})();

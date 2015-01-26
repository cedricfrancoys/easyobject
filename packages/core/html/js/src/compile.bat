rem java -jar compiler.jar --js easyObject.api.js --js_output_file easyObject.api-compiled.js
rem java -jar compiler.jar --js easyObject.choice.js --js_output_file easyObject.choice-compiled.js
rem java -jar compiler.jar --js easyObject.dropdownlist.js --js_output_file easyObject.dropdownlist-compiled.js
rem java -jar compiler.jar --js easyObject.editable.js --js_output_file easyObject.editable-compiled.js
rem java -jar compiler.jar --js easyObject.form.js --js_output_file easyObject.form-compiled.js
rem java -jar compiler.jar --js easyObject.grid.js --js_output_file easyObject.grid-compiled.js
rem java -jar compiler.jar --js easyObject.selection.js --js_output_file easyObject.selection-compiled.js
rem java -jar compiler.jar --js easyObject.tree.js --js_output_file easyObject.tree-compiled.js
rem java -jar compiler.jar --js easyObject.utils.js --js_output_file easyObject.utils-compiled.js

rem java -jar compiler.jar --js jquery-ui.panel.js --js_output_file jquery-ui.panel.min.js
rem java -jar compiler.jar --js jquery.inputmask.bundle.js --js_output_file jquery.inputmask.bundle.min.js
rem java -jar compiler.jar --js jquery-ui.daterangepicker.js --js_output_file jquery-ui.daterangepicker.min.js
rem java -jar compiler.jar --js date.js --js_output_file date.min.js


rem java -jar compiler.jar --js_output_file=out.js easyObject.api.js easyObject.choice.js easyObject.dropdownlist.js
java -jar compiler.jar --help  > help.txt
pause
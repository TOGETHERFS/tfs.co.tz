#!/usr/bin/env python3
path = '/var/www/phidlms/app/Http/Controllers/LoanController.php'
with open(path, 'r') as f:
    content = f.read()

old = """            $collateral = $request->input('collateral');
            if (!is_array($collateral)) {
                $collateral = [];
            }"""

new = """            $collateral = $request->input('collateral');
            if (!is_array($collateral)) {
                $collateral = [];
            }
            // Convert empty strings to null for decimal fields to prevent MySQL errors
            foreach (['value', 'buying_price', 'selling_price'] as $field) {
                if (array_key_exists($field, $collateral) && $collateral[$field] === '') {
                    $collateral[$field] = null;
                }
            }"""

if old in content:
    content = content.replace(old, new)
    with open(path, 'w') as f:
        f.write(content)
    print("FIXED OK")
else:
    print("ERROR: pattern not found")
    # Show context around collateral
    idx = content.find("$collateral = $request->input('collateral')")
    print(repr(content[idx:idx+300]))

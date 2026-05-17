#!/usr/bin/env python3
path = '/var/www/phidlms/app/Http/Controllers/Api/LoanController.php'
with open(path, 'r') as f:
    content = f.read()

# Remove the disbursed_by line
content = content.replace("            'disbursed_by' => $user?->id,\n", "")
content = content.replace("            'disbursed_by' => $user?->id,", "")

with open(path, 'w') as f:
    f.write(content)

print("DONE")

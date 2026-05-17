path = '/var/www/phidlms/app/Http/Controllers/ReportsController.php'
with open(path, 'r') as f:
    c = f.read()
c = c.replace("bankAccounts->sum('balance')", "bankAccounts->sum('current_balance')")
with open(path, 'w') as f:
    f.write(c)
print('DONE')

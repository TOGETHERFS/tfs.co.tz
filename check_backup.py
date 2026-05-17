import re

with open('/tmp/phidtech_db.sql', 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

tables = ['loans', 'repayments', 'clients', 'loan_schedules']
for t in tables:
    count = content.count("INSERT INTO `" + t + "`")
    print(t + ": " + str(count) + " INSERT blocks")

# Check loan IDs in this file
m = re.search(r"INSERT INTO `loans` \([^)]+\) VALUES([\s\S]+?);(?=\s*INSERT INTO|\s*--|\Z)", content)
if m:
    ids = re.findall(r'\((\d+),', m.group(1))
    if ids:
        int_ids = [int(i) for i in ids]
        print("Loan count: " + str(len(ids)))
        print("Loan ID range: " + str(min(int_ids)) + " to " + str(max(int_ids)))
        missing = [i for i in range(187, 479) if i not in int_ids]
        print("Missing loan IDs (187-478) in this file: " + str(len(missing)))
        present = [i for i in range(187, 479) if i in int_ids]
        print("Present loan IDs (187-478) in this file: " + str(len(present)))
        if present:
            print("Sample present IDs: " + str(present[:10]))
    else:
        print("No loan IDs parsed")
else:
    print("No loans INSERT block found")

# Check tenant 17 loan count in this file
t17 = re.findall(r'\(\d+,\s*17,', content.split("INSERT INTO `loans`")[1] if "INSERT INTO `loans`" in content else "")
print("Tenant 17 loans in this backup: " + str(len(t17)))

# Check repayments
m2 = re.search(r"INSERT INTO `repayments` \([^)]+\) VALUES([\s\S]+?);(?=\s*INSERT INTO|\s*--|\Z)", content)
if m2:
    rep_ids = re.findall(r'\((\d+),', m2.group(1))
    print("Repayment count: " + str(len(rep_ids)))
else:
    print("No repayments INSERT block found")

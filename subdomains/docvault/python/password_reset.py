import smtplib
import sys
import random
import math
import mysql.connector

digits = "0123456789"
OTP = ""
 

for i in range(6) :
    OTP += digits[math.floor(random.random() * 10)]
OTP = OTP
email = sys.argv[1]
print(email)

mydb = mysql.connector.connect(
  host="localhost", #127.0.0.0
  user="root",
  password="", #root
  database="whale_enterprises"
)
mycursor = mydb.cursor()

sql = "UPDATE users SET code = '%s' WHERE username = '%s'" % (OTP,email)
print(sql)

mycursor.execute(sql)

mydb.commit()


server = smtplib.SMTP('smtp.gmail.com',587)
server.starttls()
server.login('mail_id','secret_key') #should be changed
message = '''Subject: Password Reset for Whale Login - Regarding

Dear User,
We received a request to reset your password for your Whale Enterprises's Documents' account. If you did not request a password reset, please ignore this email. To reset your password, please use the following One-Time Password (OTP): {0}. 
This OTP is valid for 10 minutes. After entering the OTP, you will be able to reset your password. If you have any issues, please reach out to our support team for assistance.


Best Regards,
Whale Enterprises Pvt Ltd
1 / 1D Vilankurichi Road
Near Senthottam
Kalapatti Coimbatore
641 048
Tamilnadu
India
Telephone: +91 9566555628
'''.format(OTP)
server.sendmail("mail_id",email,message) #should be changes

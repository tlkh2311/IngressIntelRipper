IngressIntelRipper
==================

A dirty php tool to dump data from the closed Ingress Intel Map API


How to use
-------------------------

1. Setup MySQL, create a database and execute ```ingressIntelRipper.sql```
2. Open ```config.php``` and configure the MySQL-Connectioninfo, insert the data from a cookie (just split them correct and write them there; No, you don't need an account for every side, just one account is enough)
3. Get some latidute and longitude values, make sure you have 6 digits behind the comma und remove it then. ex: 50.12345 > 50.123450 > 50123450. Be careful: Scanning the whole world would not only take masses of time and space, you'll get so many data, your CPU can't handle alone
4. Think about the thing you are about to start. Read the "Last Words" and keep in mind that your accounts might get banned

Tips:
- Try to set some useful indices to speed up operations on the database
- Use a SSD for the database
- Build a cluster and calculate whatever you want

Last words
-------------
Think of privacy. You'll get data that might get in conflict with data privacy laws. There is no warrianty given. You can't sue any developer for things, you do with the tool and the data. But be advised that you get a mighty tool and with great power comes great responsibility.
Do not create stupid things or "stalk" players down. Publish only data you might want to see from yourself on the internet.
Aaaand: Do not dump and publish whole databases.
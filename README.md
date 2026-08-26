## KrazePlanetCTF

Open-source web security training platform with 260+ challenges.

## Setup

```console
curl -s "https://raw.githubusercontent.com/KrazePlanet/KrazePlanetCTF/refs/heads/main/setup.sh" | bash
```

### Demo
<img width="1903" height="911" alt="image" src="https://github.com/user-attachments/assets/a3246f98-f973-45a6-a89d-f7b4f6193006" />
<img width="1901" height="912" alt="image" src="https://github.com/user-attachments/assets/0770359a-692a-4044-9a5e-6cb6be426463" />
<img width="1905" height="914" alt="image" src="https://github.com/user-attachments/assets/c58a4265-3518-4d5f-bc27-8948e398b0bf" />
<img width="1900" height="911" alt="image" src="https://github.com/user-attachments/assets/9b777b7f-7442-457f-b6e4-91e6b7d22345" />
<img width="1904" height="912" alt="image" src="https://github.com/user-attachments/assets/aa84fd38-f6af-4ea4-9a18-74dbe065c53d" />
<img width="1904" height="911" alt="image" src="https://github.com/user-attachments/assets/ea568fb4-9325-454e-afe5-5843fbacb00a" />
<img width="1905" height="909" alt="image" src="https://github.com/user-attachments/assets/00617313-59f4-4cf3-8420-05edb6755b0c" />
<img width="1904" height="912" alt="image" src="https://github.com/user-attachments/assets/efc05401-5c8d-4325-b104-5a8d17269928" />
<img width="1903" height="913" alt="image" src="https://github.com/user-attachments/assets/1675df57-4c22-4afc-9816-935e3b9d9e53" />
<img width="1906" height="913" alt="image" src="https://github.com/user-attachments/assets/b1ce4ec1-c2b3-4442-b12b-20f58e3fee13" />


### Backups
```console
#Create zip
zip -r ../htdocs.zip .

#Extract
unzip htdocs.zip -d htdocs

find /mnt/d/Github/KrazePlanetCTF/ -mindepth 1 -maxdepth 1 ! -name '.git' -exec rm -rf -- {} +

#Back To Host
cp -af . /mnt/d/Github/KrazePlanetCTF/
```

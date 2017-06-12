Lines of Code counter
=====================

Simple utility script for Counting Lines of Code.

---

European Union Public Licence, v. 1.2

Unless required by applicable law or agreed to in writing, software
distributed under the Licence is distributed on an "AS IS" basis,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.

Contact: info@netsilik.nl  
Latest version available at: https://gitlab.com/Netsilik/LinesOfCodeCounter


Installation
------------

```
composer require netsilik/lines-of-code-counter
```

Usage
-----

Lince of Code counter is a command line scipt:


```txt
Usage: loc [OPTION]... DIRECTORY...
Count the lines of code in the files in the specified DIRECTORY(ies).

Mandatory arguments to long options are mandatory for short options too.
  -f, --file-mask              Process only files that match the file mask
      --help                   Display this help and exit
  -i, --ignore-dir=DIRECTORY   Ignore all files in the directory DIRECTORY
  -r, --recursive              Recursively process filse in sub-directories
      --version                Output version information and exit
```

Count the lines of code in all `php` files in the current directory and all subdirectories:
```txt
$ loc -rf *.php .

Parsed 11 (*.php) files out of a total of 29 files, in 12 directories and counted:
  1,051 total lines,
    157 empty lines,
    734 lines of code,
    108 comment lines,
    109 comments in total.
```

Count the lines of code in all `php` files in the src directory:
```txt
$ loc -f *.php src

Parsed 1 (*.php) file out of a total of 1 file, in 1 directory and counted:
  331 total lines,
   53 empty lines,
  247 lines of code,
   18 comment lines,
   18 comments in total.
```

Count the lines of code in all `php` and `html` files in the current directory and all subdirectories, excluding the vendor directory:
```txt
$ loc -rf *.php,*.html -i vendor .

Parsed 7 (*.php, *.html) files out of a total of 16 files, in 6 directories and counted:
  1,466 total lines,
    108 empty lines,
  1,322 lines of code,
     23 comment lines,
     33 comments in total.
```

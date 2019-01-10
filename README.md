# image-url-fuzzer

## Warning

Please make sure you are authorized to target the specific domain with this script, as otherwise this script may appear malicious.

## Overview
A php url fuzzer script that probes a specific url for any images under it within a given set of paramaters.

The script will create a multi threaded curl request to check for images on a specific domain as fast as possible.

The script is intended to be run directly through the command line as such:

`/path/to/php image-url-fuzzer.php`

## Example output

In the image example (which is also the default in the code) we are searching for a '.jpg' image on 'https://acererak.com/' with a two character alphanumeric name.

Please note, even with multi-threading and a powerful server, the less specific your query - the longer the script will take to run.

![Image of example output](https://i.imgur.com/6fgU2fR.png)


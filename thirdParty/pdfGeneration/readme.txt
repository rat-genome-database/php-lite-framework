Originally used fpdf, which is in the fpdf folder, but we have now moved to tcpdf, which seems to 
have some momentum.  It was based originally on fpdf.

the framework function newPdf uses fpdf... the framework function newPdfTc uses tcpdf.

See the demo application for examples of using both, though we recommend tcpdf.


fpdf:
Found this at:
http://www.fpdf.org/

current version installed is 1.53
Utility class is also created under util/pdf that allows basic html formatting to be applied to the text
usage (in a module function, for example: http://localhost/?module=test&func=pdf)

tcpdf:
found at:
http://www.tecnick.com/public/code/cp_dpage.php?aiocp_dp=tcpdf

No utility class needed since it itself is already a good wrapper around fpdf, and provides the
html writing functionality that the utility class was there to provide with the fpdf option.




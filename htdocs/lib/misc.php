<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see https://www.gnu.org/licenses/.
*/
function is_https(): bool {
    if (
        (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    ) {
        return true;
    }
    return false;
}

function normalise_string(string $input): string {
    // 1. Parole che rimangono minuscole (tranne a inizio frase)
    $eccezioni_minuscole = ['e', 'ed', 'o', 'od', 'il', 'lo', 'la', 'i', 'gli', 'le', 'un', 'uno', 'una', 'di', 'a', 'da', 'in', 'con', 'su', 'per', 'tra', 'fra'];
    
    // 2. Array vuoto (o precompilato) di parole da mantenere INTERAMENTE MAIUSCOLE
    $eccezioni_maiuscole = ['TPS', 'STA', 'GPOI']; 
    
    // 3. Convertiamo tutto in minuscolo e dividiamo la stringa in un array
    $parole = explode(' ', strtolower($input));
    
    // 4. Elaboriamo ogni parola
    foreach ($parole as $indice => $parola) {
        // Controlliamo se la parola originale (prima del lowercase) era nell'array delle maiuscole
        // Oppure verifichiamo la corrispondenza testuale
        $parola_originale = explode(' ', $input)[$indice] ?? '';
        
        if (in_array(strtoupper($parola_originale), $eccezioni_maiuscole, true)) {
            $parole[$indice] = strtoupper($parola_originale);
        } elseif ($indice === 0 || !in_array($parola, $eccezioni_minuscole, true)) {
            // Regola standard: maiuscola iniziale
            $parole[$indice] = ucfirst($parola);
        }
    }
    
    // 5. Ricomponiamo la stringa
    return implode(' ', $parole);
}
?>
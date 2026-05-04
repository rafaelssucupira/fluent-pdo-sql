as vezes fico usando prepare() varias vezes dentro de for. isto não é necessario né? é inperformatico?

SQL.php
:193
Exato: na maioria dos casos, não é necessário chamar prepare() dentro do for, e isso tende a ser menos performático.

Regra prática:

Faça prepare() uma vez.
Dentro do loop, só altere os valores e chame execute().
Por quê:

prepare() envolve parsing/planejamento da query no banco.
Repetir isso em toda iteração adiciona custo desnecessário.
Reutilizar o mesmo statement reduz overhead e normalmente melhora bastante em loops grandes.
Quando ainda faz sentido preparar dentro do loop:

Quando a estrutura da SQL muda a cada iteração (não só os valores).
Quando muda quantidade de placeholders de forma incompatível.
Quando você realmente precisa de queries diferentes.
Resumo direto: se a SQL é a mesma e só mudam os dados, preparar uma vez fora do loop é o ideal.
function Clockdraw(halfwidth)
{
    var canvas = document.getElementById('clock');
    if (canvas.getContext){
        var time = new Date ();
        sec=Math.PI*time.getSeconds()/30;
        min=Math.PI*time.getMinutes()/30;
        hr =Math.PI*((time.getHours()*60)+time.getMinutes())/360;
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = "rgb(250,250,250)";
        ctx.beginPath();
        ctx.arc(0,0,halfwidth*4/6,0,Math.PI+Math.PI,true);
        ctx.fill();
        ctx.fillStyle = "rgb(180,180,180)";
        ctx.save();
        for(var i=0;i<12;i++){
            ctx.rotate(Math.PI/6);ctx.beginPath();
            ctx.moveTo(-1,halfwidth*4/7);
            ctx.lineTo(+1,halfwidth*4/7);
            ctx.lineTo(+1,halfwidth*2/4);
            ctx.lineTo(-1,halfwidth*2/4);
            ctx.fill();
        }
        ctx.restore();
        ctx.fillStyle = "rgb(52,52,52)";
        ctx.save();ctx.rotate(hr);
        ctx.beginPath();ctx.moveTo(-1 , -(halfwidth-35)*0.6);
        ctx.lineTo(1 , -(halfwidth-35)*0.6);
        ctx.lineTo(5 , 0);
        ctx.lineTo(-5 , 0);
        ctx.closePath();
        ctx.fill();
        ctx.restore();
        ctx.save();
        ctx.rotate(min);
        ctx.beginPath();
        ctx.moveTo(-1 , -(halfwidth-35)*0.9);
        ctx.lineTo(1 , -(halfwidth-35)*0.9);
        ctx.lineTo(3,0);
        ctx.lineTo(-3 , 0);
        ctx.fill();
        ctx.restore();
        ctx.beginPath();
        ctx.arc(0,0,8,0,Math.PI+Math.PI,true);
        ctx.fill();
        ctx.save();
        ctx.rotate(sec);
        ctx.fillStyle = "rgb(228,0,0)";
        ctx.beginPath();
        ctx.moveTo(-0.5 , -(halfwidth-30));ctx.lineTo(0.5 , -(halfwidth-30));
        ctx.lineTo(1 , (halfwidth-30)*0.2);
        ctx.lineTo(-1 , (halfwidth-30)*0.2);
        ctx.fill();ctx.beginPath();
        ctx.arc(0,0,4,0,Math.PI+Math.PI,true);ctx.fill();ctx.restore();
    }
}
function createClock(width)
{
    var canvas = document.createElement('canvas');
    canvas.id = 'clock';
    canvas.width = width;
    canvas.height = width;
    canvas.style.position = 'fixed';
    canvas.style.top = '10px';
    canvas.style.right = '10px';
    document.body.appendChild(canvas);
    if (canvas.getContext)
    {
        var ctx = canvas.getContext('2d');

        //绘制表盘
        ctx.fillStyle = "rgba(60,60,60,0.5)";
        ctx.beginPath();
        ctx.moveTo(width-34,0);
        ctx.quadraticCurveTo(width,0,width,34);
        ctx.lineTo(width,width-34);
        ctx.quadraticCurveTo(width,width,width-34,width);
        ctx.lineTo(34,width);
        ctx.quadraticCurveTo(0,width,0,width-34);
        ctx.lineTo(0,34);
        ctx.quadraticCurveTo(0,0,34,0);
        ctx.fill();

        ctx.fillStyle = "rgba(255,255,255,0.5)";
        ctx.beginPath();
        ctx.moveTo(width-35,5);
        ctx.quadraticCurveTo(width-5,5,width-5,35);
        ctx.lineTo(width-5,width/2);
        ctx.lineTo(5,width/2);
        ctx.lineTo(5,35);
        ctx.quadraticCurveTo(5,5,35,5);
        ctx.fill();

        ctx.fillStyle = "rgba(220,220,220,0.5)";
        ctx.beginPath();
        ctx.moveTo(width-5,width/2);
        ctx.lineTo(width-5,width-35);
        ctx.quadraticCurveTo(width-5,width-5,width-35,width-5);
        ctx.lineTo(35,width-5);
        ctx.quadraticCurveTo(5,width-5,5,width-35);
        ctx.lineTo(5,width/2);
        ctx.fill();

        ctx.fillStyle = "rgba(60,60,60,0.5)";
        ctx.beginPath();
        ctx.arc(width/2,width/2,width*4/12+5,0,Math.PI+Math.PI,true);
        ctx.fill();
        ctx.translate(width/2,width/2);//移动中心
    }
    setInterval('Clockdraw('+width/2+')',1000);
            //添加监听
            canvas.addEventListener("mouseover",function(){
                if(document.getElementById('clock').style.top=="10px")
                for(var i=10;i<=490;i+=20){
                setTimeout("document.getElementById('clock').style.top ='"
                    +i+"px'",i);
                }
                else if(document.getElementById('clock').style.top=="490px")
                for(var i=10;i<=490;i+=20){
                setTimeout("document.getElementById('clock').style.top ='"
                    +(500-i)+"px'",i);
                }
                },false);
            }
            //在1.2秒后生成一个canvas组件，您也可在您喜欢的时间生成组件。246代表时钟的宽度。
            if(navigator.appName!="Microsoft Internet Explorer")
            setTimeout("createClock(120)",500);
